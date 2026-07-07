/**
 * httpdocs/public/assets/js/site.js
 * 公開画面の操作と、管理画面の翻訳補助・slug自動入力を提供します。
 */

document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelector('[data-nav]')?.classList.toggle('is-open');
  });
});

document.querySelectorAll('[data-gallery-thumb]').forEach((button) => {
  button.addEventListener('click', () => {
    const target = document.querySelector('[data-gallery-main]');
    const src = button.getAttribute('data-src');
    if (target && src) {
      target.setAttribute('src', src);
    }
  });
});

/**
 * 指定フォーム内の単一入力要素を取得します。
 * 同名の複数要素が返るケースを避け、valueを持つ通常のinput/textarea/selectだけを扱います。
 */
const getFormControl = (form, name) => {
  const control = form?.elements?.namedItem(name);
  return control && typeof control.value === 'string' ? control : null;
};

/**
 * 英語欄やタイトルから、URLに使えるASCII slugを生成します。
 * AI英訳で英語欄へ反映された後の文字列を主な入力として想定します。
 */
const toSlug = (value) => {
  const slug = String(value || '')
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');
  return slug.slice(0, 120);
};

/**
 * PleskプレビューURL配下でも管理画面Ajaxの送信先が同じprefixを保つように解決します。
 */
const resolveAdminEndpoint = (endpoint) => {
  if (!endpoint) {
    return '';
  }
  if (/^https?:\/\//i.test(endpoint)) {
    return endpoint;
  }
  const currentPath = window.location.pathname;
  const adminIndex = currentPath.indexOf('/admin/');
  const adminPrefix = adminIndex >= 0 ? currentPath.slice(0, adminIndex) : '';
  if (endpoint.startsWith('/admin/')) {
    return `${window.location.origin}${adminPrefix}${endpoint}`;
  }
  return new URL(endpoint, window.location.href).toString();
};

/**
 * 管理画面フォームのslug欄を初期化します。
 * 空欄時だけ自動更新し、手入力済みのslugは明示的な再生成操作がない限り保持します。
 */
document.querySelectorAll('form.admin-form').forEach((form) => {
  const slugInput = getFormControl(form, 'slug');
  if (!slugInput) {
    return;
  }

  const sourceNames = ['name_en', 'title_en', 'name_ja', 'title_ja'];
  const sourceControls = sourceNames
    .map((name) => getFormControl(form, name))
    .filter(Boolean);

  if (!sourceControls.length) {
    return;
  }

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'button secondary slug-refresh-button';
  button.textContent = 'slug再生成';
  slugInput.insertAdjacentElement('afterend', button);

  slugInput.dataset.slugMode = slugInput.value.trim() === '' ? 'auto' : 'manual';

  const currentCandidate = () => {
    const preferred = ['name_en', 'title_en', 'name_ja', 'title_ja'];
    for (const name of preferred) {
      const control = getFormControl(form, name);
      if (control?.value.trim()) {
        return control.value.trim();
      }
    }
    return '';
  };

  const refreshSlug = (force = false) => {
    if (!force && slugInput.dataset.slugMode === 'manual' && slugInput.value.trim() !== '') {
      return;
    }
    const nextSlug = toSlug(currentCandidate());
    if (nextSlug) {
      slugInput.value = nextSlug;
      slugInput.dataset.slugMode = 'auto';
    }
  };

  slugInput.addEventListener('input', () => {
    slugInput.dataset.slugMode = slugInput.value.trim() === '' ? 'auto' : 'manual';
  });

  sourceControls.forEach((control) => {
    control.addEventListener('input', () => refreshSlug(false));
  });

  button.addEventListener('click', () => refreshSlug(true));
  form.addEventListener('aerotech:english-applied', () => refreshSlug(false));
  refreshSlug(false);
});

/**
 * 管理画面の日本語欄からAI英訳を実行し、補助用の下書き表示も扱います。
 */
document.querySelectorAll('[data-translation-helper]').forEach((helper) => {
  const form = helper.closest('form');
  const draftArea = helper.querySelector('[data-translation-drafts]');
  const status = helper.querySelector('[data-translation-status]');
  const data = helper.querySelector('[data-translation-pairs]');
  const aiButton = helper.querySelector('[data-ai-translate]');

  if (!form || !draftArea || !data) {
    return;
  }

  let pairs = [];
  try {
    pairs = JSON.parse(data.textContent || '[]');
  } catch (error) {
    pairs = [];
  }

  const setStatus = (message) => {
    if (status) {
      status.textContent = message;
    }
  };

  const sourceFields = () => pairs
    .map((pair) => {
      const source = getFormControl(form, pair.source);
      return {
        label: pair.label || pair.target,
        target: pair.target,
        source: source?.value.trim() || '',
      };
    })
    .filter((field) => field.source !== '');

  const applyTranslations = (translations, slugCandidate = '') => {
    let applied = 0;
    Object.entries(translations || {}).forEach(([targetName, text]) => {
      const target = getFormControl(form, targetName);
      if (!target || typeof text !== 'string' || text.trim() === '') {
        return;
      }
      target.value = text.trim();
      target.dispatchEvent(new Event('input', { bubbles: true }));
      applied += 1;
    });

    const slugInput = getFormControl(form, 'slug');
    const slug = toSlug(slugCandidate);
    if (slugInput && slug && (slugInput.value.trim() === '' || slugInput.dataset.slugMode === 'auto')) {
      slugInput.value = slug;
      slugInput.dataset.slugMode = 'auto';
    }

    form.dispatchEvent(new CustomEvent('aerotech:english-applied', { bubbles: true }));
    return applied;
  };

  const runAiTranslate = async () => {
    const endpoint = resolveAdminEndpoint(helper.getAttribute('data-ai-endpoint') || '');
    const csrf = helper.getAttribute('data-csrf');
    const fields = sourceFields();

    if (!endpoint || !csrf) {
      setStatus('AI英訳の送信先が設定されていません。');
      return;
    }
    if (!fields.length) {
      setStatus('日本語欄に入力するとAI英訳できます。');
      return;
    }

    if (aiButton) {
      aiButton.disabled = true;
      aiButton.textContent = '英訳中...';
    }
    setStatus('AIで英訳しています。保存はまだ行われません。');

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
        },
        body: JSON.stringify({
          context: document.querySelector('h1')?.textContent.trim() || '',
          fields,
        }),
      });
      const result = await response.json();
      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'AI英訳に失敗しました。');
      }

      const applied = applyTranslations(result.translations || {}, result.slug || '');
      setStatus(applied > 0
        ? `AI英訳を${applied}件反映しました。内容を確認してから保存してください。`
        : 'AI英訳結果に反映できる項目がありませんでした。');
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'AI英訳に失敗しました。');
    } finally {
      if (aiButton) {
        aiButton.disabled = false;
        aiButton.textContent = 'AIで英訳';
      }
    }
  };

  const buildDrafts = () => {
    draftArea.replaceChildren();
    pairs.forEach((pair) => {
      const source = getFormControl(form, pair.source);
      const text = source?.value.trim() || '';
      if (!text) {
        return;
      }

      const draft = document.createElement('article');
      draft.className = 'translation-draft';
      draft.dataset.translationTarget = pair.target;

      const heading = document.createElement('h3');
      heading.textContent = pair.label || pair.target;

      const body = document.createElement('div');
      body.className = 'translation-draft-text';
      body.textContent = text;

      draft.append(heading, body);
      draftArea.append(draft);
    });

    const count = draftArea.children.length;
    helper.classList.toggle('is-empty', count === 0);
    setStatus(count > 0
      ? `下書き${count}件を更新しました。必要に応じて内容を確認できます。`
      : '日本語欄に入力すると翻訳用下書きが表示されます。');
  };

  const applyDrafts = () => {
    const translations = {};
    draftArea.querySelectorAll('[data-translation-target]').forEach((draft) => {
      const targetName = draft.getAttribute('data-translation-target');
      const text = draft.querySelector('.translation-draft-text')?.textContent.trim() || '';
      if (!targetName || !text) {
        return;
      }
      translations[targetName] = text;
    });

    const applied = applyTranslations(translations);
    setStatus(applied > 0 ? `英語欄に${applied}件反映しました。` : '反映できる下書きがありません。');
  };

  aiButton?.addEventListener('click', runAiTranslate);
  helper.querySelector('[data-translation-build]')?.addEventListener('click', buildDrafts);
  helper.querySelector('[data-translation-apply]')?.addEventListener('click', applyDrafts);
  buildDrafts();
});

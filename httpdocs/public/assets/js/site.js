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

const normalizeRichEditorHtml = (html) => String(html || '')
  .replace(/<font\s+[^>]*color=["']?([^"'>\s]+)["']?[^>]*>/gi, '<span style="color: $1;">')
  .replace(/<\/font>/gi, '</span>');

document.querySelectorAll('textarea[data-rich-editor]').forEach((textarea) => {
  if (textarea.dataset.richEditorReady === '1') {
    return;
  }
  textarea.dataset.richEditorReady = '1';

  const form = textarea.closest('form');
  const endpoint = resolveAdminEndpoint(textarea.getAttribute('data-rich-ai-endpoint') || '');
  const csrf = textarea.getAttribute('data-rich-ai-csrf') || '';
  const wrapper = document.createElement('div');
  const toolbar = document.createElement('div');
  const visual = document.createElement('div');
  const status = document.createElement('div');
  let mode = 'visual';

  wrapper.className = 'rich-editor';
  toolbar.className = 'rich-editor-toolbar';
  visual.className = 'rich-editor-visual rich-content';
  visual.contentEditable = 'true';
  visual.setAttribute('role', 'textbox');
  visual.setAttribute('aria-multiline', 'true');
  status.className = 'rich-editor-status';

  const syncToTextarea = () => {
    textarea.value = normalizeRichEditorHtml(visual.innerHTML).trim();
  };
  const setHtml = (html) => {
    visual.innerHTML = normalizeRichEditorHtml(html);
    syncToTextarea();
  };
  const setStatus = (message) => {
    status.textContent = message;
  };
  const runCommand = (command, value = null) => {
    visual.focus();
    document.execCommand(command, false, value);
    setHtml(visual.innerHTML);
  };
  const makeButton = (label, action, className = 'button secondary') => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.textContent = label;
    button.addEventListener('click', action);
    return button;
  };
  const setMode = (nextMode) => {
    if (nextMode === 'visual') {
      visual.innerHTML = normalizeRichEditorHtml(textarea.value);
      textarea.hidden = true;
      visual.hidden = false;
      mode = 'visual';
      setStatus('WYSIWYG編集中です。');
      return;
    }
    syncToTextarea();
    textarea.hidden = false;
    visual.hidden = true;
    mode = 'html';
    setStatus('HTMLソース編集中です。');
  };

  const visualButton = makeButton('WYSIWYG', () => setMode('visual'));
  const htmlButton = makeButton('HTML', () => setMode('html'));
  const boldButton = makeButton('B', () => runCommand('bold'));
  const italicButton = makeButton('I', () => runCommand('italic'));
  const ulButton = makeButton('箇条書き', () => runCommand('insertUnorderedList'));
  const olButton = makeButton('番号リスト', () => runCommand('insertOrderedList'));
  const linkButton = makeButton('リンク', () => {
    const href = window.prompt('リンクURLを入力してください。', 'https://');
    if (href && href.trim() !== '' && href.trim() !== 'https://') {
      runCommand('createLink', href.trim());
      visual.querySelectorAll('a').forEach((anchor) => {
        anchor.target = '_blank';
        anchor.rel = 'noopener noreferrer';
      });
      syncToTextarea();
    }
  });
  const cleanButton = makeButton('AIでHTML整形', async () => {
    if (mode === 'visual') {
      syncToTextarea();
    }
    if (!endpoint || !csrf) {
      setStatus('AI整形の送信先が設定されていません。');
      return;
    }
    if (textarea.value.trim() === '') {
      setStatus('整形するHTMLがありません。');
      return;
    }

    cleanButton.disabled = true;
    cleanButton.textContent = '整形中...';
    setStatus('AIでHTML構文を整えています。保存はまだ行われません。');
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
        },
        body: JSON.stringify({
          context: document.querySelector('h1')?.textContent.trim() || '',
          html: textarea.value,
        }),
      });
      const result = await response.json();
      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'HTML整形に失敗しました。');
      }
      textarea.value = String(result.html || '').trim();
      setMode('visual');
      setStatus('HTMLを整形しました。内容を確認して保存してください。');
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'HTML整形に失敗しました。');
    } finally {
      cleanButton.disabled = false;
      cleanButton.textContent = 'AIでHTML整形';
    }
  });

  const colorLabel = document.createElement('label');
  colorLabel.className = 'rich-editor-color';
  colorLabel.textContent = '文字色';
  const colorInput = document.createElement('input');
  colorInput.type = 'color';
  colorInput.value = '#4dd8ff';
  colorInput.addEventListener('input', () => runCommand('foreColor', colorInput.value));
  colorLabel.append(colorInput);

  toolbar.append(visualButton, htmlButton, boldButton, italicButton, ulButton, olButton, linkButton, colorLabel, cleanButton);
  wrapper.append(toolbar, visual, status);
  textarea.insertAdjacentElement('afterend', wrapper);

  visual.addEventListener('input', syncToTextarea);
  textarea.addEventListener('input', () => {
    if (mode === 'visual') {
      visual.innerHTML = normalizeRichEditorHtml(textarea.value);
    }
  });
  form?.addEventListener('submit', () => {
    if (mode === 'visual') {
      syncToTextarea();
    }
  });
  setMode('visual');
});

document.querySelectorAll('textarea[data-html-fragment-helper]').forEach((textarea) => {
  if (textarea.dataset.htmlFragmentHelperReady === '1') {
    return;
  }
  textarea.dataset.htmlFragmentHelperReady = '1';

  const endpoint = resolveAdminEndpoint(textarea.getAttribute('data-fragment-ai-endpoint') || '');
  const csrf = textarea.getAttribute('data-fragment-ai-csrf') || '';
  const helper = document.createElement('div');
  const toolbar = document.createElement('div');
  const status = document.createElement('div');
  const colorLabel = document.createElement('label');
  const colorInput = document.createElement('input');

  helper.className = 'html-fragment-helper';
  toolbar.className = 'rich-editor-toolbar';
  status.className = 'rich-editor-status';
  colorLabel.className = 'rich-editor-color';
  colorLabel.textContent = '文字色';
  colorInput.type = 'color';
  colorInput.value = '#e12d2d';
  colorLabel.append(colorInput);

  const setStatus = (message) => {
    status.textContent = message;
  };
  const selectedText = () => textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
  const replaceSelection = (replacement) => {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    textarea.setRangeText(replacement, start, end, 'select');
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.focus();
  };
  const wrapSelection = (before, after) => {
    const selected = selectedText();
    if (!selected) {
      setStatus('装飾する文字を選択してください。');
      return;
    }
    if (selected.includes('|')) {
      setStatus('区切り記号を含まない範囲を選択してください。');
      return;
    }
    replaceSelection(`${before}${selected}${after}`);
    setStatus('選択文字へHTMLタグを追加しました。');
  };
  const makeButton = (label, action) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'button secondary';
    button.textContent = label;
    button.addEventListener('click', action);
    return button;
  };

  const colorButton = makeButton('選択文字色', () => {
    wrapSelection(`<span style="color: ${colorInput.value};">`, '</span>');
  });
  const strongButton = makeButton('太字', () => {
    wrapSelection('<strong>', '</strong>');
  });
  const cleanButton = makeButton('AIで選択HTML整形', async () => {
    const selected = selectedText();
    if (!selected) {
      setStatus('整形するHTML断片を選択してください。');
      return;
    }
    if (selected.includes('|')) {
      setStatus('区切り記号を含まない範囲を選択してください。');
      return;
    }
    if (!endpoint || !csrf) {
      setStatus('AI整形の送信先が設定されていません。');
      return;
    }

    cleanButton.disabled = true;
    cleanButton.textContent = '整形中...';
    setStatus('選択HTMLをAIで整えています。保存はまだ行われません。');
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
        },
        body: JSON.stringify({
          context: document.querySelector('h1')?.textContent.trim() || '',
          html: selected,
        }),
      });
      const result = await response.json();
      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'HTML整形に失敗しました。');
      }
      replaceSelection(String(result.html || '').trim());
      setStatus('選択HTMLを整形しました。内容を確認して保存してください。');
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'HTML整形に失敗しました。');
    } finally {
      cleanButton.disabled = false;
      cleanButton.textContent = 'AIで選択HTML整形';
    }
  });

  toolbar.append(colorLabel, colorButton, strongButton, cleanButton);
  helper.append(toolbar, status);
  textarea.insertAdjacentElement('afterend', helper);
});

document.querySelectorAll('[data-spec-editor]').forEach((editor) => {
  if (editor.dataset.specEditorReady === '1') {
    return;
  }
  editor.dataset.specEditorReady = '1';

  document.querySelector('textarea[name="specs_text"]')?.closest('label')?.classList.add('legacy-spec-textarea');

  const form = editor.closest('form');
  const rowsContainer = editor.querySelector('[data-spec-rows]');
  const template = editor.querySelector('template[data-spec-template]');
  const endpoint = resolveAdminEndpoint(editor.getAttribute('data-spec-ai-endpoint') || '');
  const csrf = editor.getAttribute('data-spec-ai-csrf') || '';
  const status = editor.querySelector('[data-spec-status]');
  const colorInput = editor.querySelector('[data-spec-color]');
  let activeField = null;
  let activeRange = null;

  const setStatus = (message) => {
    if (status) {
      status.textContent = message;
    }
  };
  const fieldHtmlValue = (field) => {
    if (!field || field.textContent.trim() === '') {
      return '';
    }
    return normalizeRichEditorHtml(field.innerHTML).trim();
  };
  const syncField = (field) => {
    const source = field?.closest('.spec-editor-cell')?.querySelector('textarea[data-spec-source]');
    if (source) {
      source.value = fieldHtmlValue(field);
    }
  };
  const syncAll = () => {
    editor.querySelectorAll('[data-spec-field]').forEach(syncField);
  };
  const saveSelection = () => {
    const selection = window.getSelection();
    if (!activeField || !selection || selection.rangeCount === 0) {
      return;
    }
    if (activeField.contains(selection.anchorNode) && activeField.contains(selection.focusNode)) {
      activeRange = selection.getRangeAt(0).cloneRange();
    }
  };
  const restoreSelection = () => {
    if (!activeField) {
      return false;
    }
    activeField.focus();
    if (activeRange) {
      const selection = window.getSelection();
      selection?.removeAllRanges();
      selection?.addRange(activeRange);
    }
    return true;
  };
  const setActiveField = (field) => {
    activeField = field;
    editor.querySelectorAll('[data-spec-field].is-active').forEach((item) => item.classList.remove('is-active'));
    field.classList.add('is-active');
    setStatus('選択中のセルを編集できます。');
    setTimeout(saveSelection, 0);
  };
  const runSpecCommand = (command, value = null) => {
    if (!restoreSelection()) {
      setStatus('編集するセルをクリックしてください。');
      return;
    }
    document.execCommand(command, false, value);
    syncField(activeField);
    saveSelection();
  };
  const setFieldHtml = (field, html) => {
    field.innerHTML = normalizeRichEditorHtml(html);
    syncField(field);
  };
  const initRow = (row) => {
    row.querySelectorAll('[data-spec-field]').forEach((field) => {
      if (field.dataset.specFieldReady === '1') {
        return;
      }
      field.dataset.specFieldReady = '1';
      field.addEventListener('focus', () => setActiveField(field));
      field.addEventListener('mouseup', saveSelection);
      field.addEventListener('keyup', saveSelection);
      field.addEventListener('input', () => {
        syncField(field);
        saveSelection();
      });
      syncField(field);
    });
  };
  const rowItems = () => [...editor.querySelectorAll('[data-spec-row]')];
  const addRow = () => {
    if (!template || !rowsContainer) {
      return;
    }
    const row = template.content.firstElementChild.cloneNode(true);
    rowsContainer.append(row);
    initRow(row);
    row.querySelector('[data-spec-field]')?.focus();
  };
  const clearRow = (row) => {
    row.querySelectorAll('[data-spec-field]').forEach((field) => setFieldHtml(field, ''));
  };
  const removeRow = (row) => {
    const rows = rowItems();
    if (rows.length <= 1) {
      clearRow(row);
      return;
    }
    row.remove();
    activeField = null;
    activeRange = null;
    setStatus('SPEC行を削除しました。');
  };

  editor.querySelectorAll('[data-spec-row]').forEach(initRow);

  editor.querySelector('[data-spec-add-row]')?.addEventListener('click', addRow);
  editor.querySelector('[data-spec-toolbar]')?.addEventListener('mousedown', (event) => {
    if (event.target instanceof HTMLButtonElement) {
      event.preventDefault();
    }
  });
  editor.querySelectorAll('[data-spec-command]').forEach((button) => {
    button.addEventListener('click', () => runSpecCommand(button.getAttribute('data-spec-command') || ''));
  });
  editor.querySelector('[data-spec-apply-color]')?.addEventListener('click', () => {
    runSpecCommand('foreColor', colorInput?.value || '#e12d2d');
  });
  editor.querySelector('[data-spec-link]')?.addEventListener('click', () => {
    const href = window.prompt('リンクURLを入力してください。', 'https://');
    if (href && href.trim() !== '' && href.trim() !== 'https://') {
      runSpecCommand('createLink', href.trim());
      activeField?.querySelectorAll('a').forEach((anchor) => {
        anchor.target = '_blank';
        anchor.rel = 'noopener noreferrer';
      });
      if (activeField) {
        syncField(activeField);
      }
    }
  });
  editor.querySelector('[data-spec-ai-clean]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    if (!activeField) {
      setStatus('AI整形するセルをクリックしてください。');
      return;
    }
    syncField(activeField);
    const html = fieldHtmlValue(activeField);
    if (html === '') {
      setStatus('整形するHTMLがありません。');
      return;
    }
    if (!endpoint || !csrf) {
      setStatus('AI整形の送信先が設定されていません。');
      return;
    }

    button.disabled = true;
    button.textContent = '整形中...';
    setStatus('選択中のセルHTMLをAIで整えています。保存はまだ行われません。');
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
        },
        body: JSON.stringify({
          context: document.querySelector('h1')?.textContent.trim() || '',
          html,
        }),
      });
      const result = await response.json();
      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'HTML整形に失敗しました。');
      }
      setFieldHtml(activeField, String(result.html || '').trim());
      setStatus('セルHTMLを整形しました。内容を確認して保存してください。');
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'HTML整形に失敗しました。');
    } finally {
      button.disabled = false;
      button.textContent = 'AIでセルHTML整形';
    }
  });
  editor.addEventListener('click', (event) => {
    const row = event.target.closest?.('[data-spec-row]');
    if (!row) {
      return;
    }
    if (event.target.closest('[data-spec-remove]')) {
      removeRow(row);
    } else if (event.target.closest('[data-spec-move-up]')) {
      const previous = row.previousElementSibling?.matches('[data-spec-row]') ? row.previousElementSibling : null;
      if (previous) {
        rowsContainer.insertBefore(row, previous);
        setStatus('SPEC行を上へ移動しました。');
      }
    } else if (event.target.closest('[data-spec-move-down]')) {
      const next = row.nextElementSibling?.matches('[data-spec-row]') ? row.nextElementSibling : null;
      if (next) {
        rowsContainer.insertBefore(next, row);
        setStatus('SPEC行を下へ移動しました。');
      }
    }
  });
  form?.addEventListener('submit', syncAll);
});

const fileMatchesAccept = (file, accept) => {
  const rules = String(accept || '')
    .split(',')
    .map((rule) => rule.trim().toLowerCase())
    .filter(Boolean);
  if (!rules.length) {
    return true;
  }
  const fileName = file.name.toLowerCase();
  const fileType = file.type.toLowerCase();
  return rules.some((rule) => {
    if (rule.startsWith('.')) {
      return fileName.endsWith(rule);
    }
    if (rule === 'application/pdf' && fileName.endsWith('.pdf')) {
      return true;
    }
    if (rule === 'image/*' && /\.(jpe?g|png|gif|webp)$/i.test(fileName)) {
      return true;
    }
    if (rule.endsWith('/*')) {
      return fileType.startsWith(rule.slice(0, -1));
    }
    return fileType === rule;
  });
};

const setFileInputFiles = (input, files) => {
  if (typeof DataTransfer === 'undefined') {
    return false;
  }
  const selected = Array.from(files || [])
    .filter((file) => fileMatchesAccept(file, input.accept))
    .slice(0, input.multiple ? undefined : 1);
  if (!selected.length) {
    return false;
  }
  const transfer = new DataTransfer();
  selected.forEach((file) => transfer.items.add(file));
  input.files = transfer.files;
  input.dispatchEvent(new Event('change', { bubbles: true }));
  return true;
};

const fileInputLabel = (input) => {
  const files = Array.from(input.files || []);
  if (!files.length) {
    return 'ファイルをここへドロップ、またはクリックして選択';
  }
  return files.map((file) => file.name).join(' / ');
};

document.querySelectorAll('.admin-form input[type="file"]').forEach((input) => {
  const zone = input.closest('label') || input.parentElement;
  if (!zone || zone.dataset.fileDropReady === '1') {
    return;
  }
  zone.dataset.fileDropReady = '1';
  zone.classList.add('file-drop-zone');
  input.classList.add('file-drop-input');

  const status = document.createElement('span');
  status.className = 'file-drop-status';
  zone.append(status);

  const updateStatus = () => {
    status.textContent = fileInputLabel(input);
  };

  ['dragenter', 'dragover'].forEach((type) => {
    zone.addEventListener(type, (event) => {
      event.preventDefault();
      zone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach((type) => {
    zone.addEventListener(type, () => {
      zone.classList.remove('is-dragover');
    });
  });

  zone.addEventListener('drop', (event) => {
    event.preventDefault();
    if (!setFileInputFiles(input, event.dataTransfer?.files || [])) {
      status.textContent = '対応しているファイルをドロップしてください。';
    }
  });

  input.addEventListener('change', updateStatus);
  updateStatus();
});

document.querySelectorAll('[data-price-list-form]').forEach((form) => {
  const button = form.querySelector('[data-price-list-ai-assist]');
  const status = form.querySelector('[data-price-list-ai-status]');
  const pdfInput = getFormControl(form, 'pdf');
  if (!button || !status || !pdfInput) {
    return;
  }

  const setStatus = (message) => {
    status.textContent = message;
  };

  const setValue = (name, value) => {
    const control = getFormControl(form, name);
    if (!control || String(value || '').trim() === '') {
      return false;
    }
    control.value = String(value).trim();
    control.dispatchEvent(new Event('input', { bubbles: true }));
    control.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
  };

  button.addEventListener('click', async () => {
    const pdf = pdfInput.files?.[0];
    const csrf = getFormControl(form, '_csrf')?.value || '';
    const endpoint = resolveAdminEndpoint(form.getAttribute('data-price-list-ai-endpoint') || '');
    if (!pdf) {
      setStatus('先に価格表PDFをアップロードしてください。');
      return;
    }
    if (!endpoint || !csrf) {
      setStatus('AI補助の送信先が設定されていません。');
      return;
    }

    const formData = new FormData();
    formData.append('_csrf', csrf);
    formData.append('pdf', pdf, pdf.name);
    button.disabled = true;
    button.textContent = 'AI判定中...';
    setStatus('PDF内容からブランド・タイトル・公開日を判定しています。');

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        body: formData,
      });
      const result = await response.json();
      if (!response.ok || !result.ok) {
        throw new Error(result.message || 'AI判定に失敗しました。');
      }

      let applied = 0;
      applied += setValue('category_id', result.category_id) ? 1 : 0;
      applied += setValue('title_ja', result.title_ja) ? 1 : 0;
      applied += setValue('title_en', result.title_en) ? 1 : 0;
      applied += setValue('published_at', result.published_at) ? 1 : 0;
      const confidenceLabel = { high: '高', medium: '中', low: '低' }[result.confidence] || result.confidence || '不明';
      setStatus(`AI補助を${applied}項目へ反映しました。確度: ${confidenceLabel}。${result.reason || '内容を確認して保存してください。'}`);
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'AI判定に失敗しました。');
    } finally {
      button.disabled = false;
      button.textContent = 'PDFから入力補助';
    }
  });
});

document.querySelectorAll('[data-sortable-images]').forEach((list) => {
  let dragged = null;

  const itemAtPoint = (x, y) => {
    if (!dragged) {
      return null;
    }
    dragged.style.pointerEvents = 'none';
    const target = document.elementFromPoint(x, y)?.closest('[data-image-sort-item]');
    dragged.style.pointerEvents = '';
    return target?.parentElement === list ? target : null;
  };

  const shouldInsertBefore = (target, x, y) => {
    const rect = target.getBoundingClientRect();
    const columns = getComputedStyle(list)
      .gridTemplateColumns
      .split(' ')
      .filter((column) => column && column !== 'none').length;
    if (columns <= 1) {
      return y < rect.top + rect.height / 2;
    }
    return x < rect.left + rect.width / 2;
  };

  const moveDraggedItem = (x, y) => {
    const target = itemAtPoint(x, y);
    if (!dragged || !target || target === dragged) {
      return;
    }

    const before = shouldInsertBefore(target, x, y);
    const reference = before ? target : target.nextElementSibling;
    if (reference !== dragged) {
      list.insertBefore(dragged, reference);
    }
  };

  const finishDrag = (handle, pointerId) => {
    dragged?.classList.remove('is-dragging');
    list.classList.remove('is-sorting');
    try {
      handle.releasePointerCapture(pointerId);
    } catch (error) {
      // Pointer capture may already be released by the browser.
    }
    dragged = null;
  };

  list.querySelectorAll('[data-image-sort-item]').forEach((item) => {
    item.removeAttribute('draggable');
    const handle = item.querySelector('.drag-handle');
    if (!handle) {
      return;
    }

    handle.addEventListener('keydown', (event) => {
      const previous = event.key === 'ArrowUp' || event.key === 'ArrowLeft';
      const next = event.key === 'ArrowDown' || event.key === 'ArrowRight';
      if (!previous && !next) {
        return;
      }
      event.preventDefault();
      if (previous && item.previousElementSibling) {
        list.insertBefore(item, item.previousElementSibling);
      }
      if (next && item.nextElementSibling) {
        list.insertBefore(item, item.nextElementSibling.nextElementSibling);
      }
      item.classList.add('is-reordered');
      window.setTimeout(() => item.classList.remove('is-reordered'), 280);
    });

    handle.addEventListener('pointerdown', (event) => {
      if (event.button !== 0) {
        return;
      }
      event.preventDefault();
      dragged = item;
      item.classList.add('is-dragging');
      list.classList.add('is-sorting');
      try {
        handle.setPointerCapture(event.pointerId);
      } catch (error) {
        // Older browser builds can omit pointer capture support.
      }

      const onPointerMove = (moveEvent) => {
        moveEvent.preventDefault();
        moveDraggedItem(moveEvent.clientX, moveEvent.clientY);
      };
      const onPointerUp = (upEvent) => {
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        window.removeEventListener('pointercancel', onPointerUp);
        finishDrag(handle, upEvent.pointerId);
      };

      window.addEventListener('pointermove', onPointerMove, { passive: false });
      window.addEventListener('pointerup', onPointerUp);
      window.addEventListener('pointercancel', onPointerUp);
    });
  });
});

document.querySelectorAll('[data-calendar-editor]').forEach((editor) => {
  const selected = new Set();
  const dayButtons = new Map();
  const eventPanels = new Map();
  const eventNameInputs = new Map();
  const emptyEventState = editor.querySelector('[data-calendar-event-empty]');
  const calendarAiEndpoint = resolveAdminEndpoint(editor.getAttribute('data-calendar-ai-endpoint') || '');
  const calendarAiCsrf = editor.getAttribute('data-calendar-ai-csrf') || '';
  const labels = {
    '': '基本設定',
    open: '営業日',
    closed: '休日',
    am_closed: '午前休',
    pm_closed: '午後休',
  };

  const setActiveDate = (date) => {
    dayButtons.forEach((button, buttonDate) => {
      button.classList.toggle('is-active', buttonDate === date);
    });
    eventPanels.forEach((panel, panelDate) => {
      panel.hidden = panelDate !== date;
    });
    if (emptyEventState) {
      emptyEventState.hidden = eventPanels.has(date);
    }
  };

  const updateEventBadge = (date) => {
    const day = dayButtons.get(date);
    const inputs = eventNameInputs.get(date) || [];
    if (!day) {
      return;
    }
    const hasEvent = inputs.some((input) => input.value.trim() !== '');
    day.classList.toggle('has-event', hasEvent);
    const badge = day.querySelector('[data-calendar-event-badge]');
    if (badge) {
      badge.hidden = !hasEvent;
    }
  };

  editor.querySelectorAll('[data-calendar-event-panel]').forEach((panel) => {
    const date = panel.getAttribute('data-calendar-event-panel');
    if (date) {
      eventPanels.set(date, panel);
    }
  });

  editor.querySelectorAll('[data-calendar-event-name]').forEach((input) => {
    const date = input.getAttribute('data-calendar-event-name');
    if (!date) {
      return;
    }
    if (!eventNameInputs.has(date)) {
      eventNameInputs.set(date, []);
    }
    eventNameInputs.get(date).push(input);
    input.addEventListener('input', () => updateEventBadge(date));
  });

  editor.querySelectorAll('[data-calendar-event-ai]').forEach((button) => {
    button.addEventListener('click', async () => {
      const panel = button.closest('[data-calendar-event-panel]');
      const jaInput = panel?.querySelector('[data-calendar-event-ja]');
      const enInput = panel?.querySelector('[data-calendar-event-en]');
      const status = panel?.querySelector('[data-calendar-event-ai-status]');
      const date = panel?.getAttribute('data-calendar-event-panel') || '';
      const source = jaInput?.value.trim() || '';

      const setStatus = (message) => {
        if (status) {
          status.textContent = message;
        }
      };

      if (!panel || !jaInput || !enInput) {
        setStatus('AI英訳の入力欄を確認できません。');
        return;
      }
      if (!calendarAiEndpoint || !calendarAiCsrf) {
        setStatus('AI英訳の送信先が設定されていません。');
        return;
      }
      if (!source) {
        setStatus('日本語イベント名を入力してください。');
        return;
      }

      button.disabled = true;
      button.textContent = '英訳中...';
      setStatus('AIで英訳しています。保存はまだ行われません。');

      try {
        const response = await fetch(calendarAiEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': calendarAiCsrf,
          },
          body: JSON.stringify({
            context: `営業日カレンダーのイベント名 ${date}`,
            fields: [{
              label: 'イベント名',
              target: 'event_name_en',
              source,
            }],
          }),
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
          throw new Error(result.message || 'AI英訳に失敗しました。');
        }

        const translated = String(result.translations?.event_name_en || '').trim();
        if (!translated) {
          setStatus('AI英訳結果が空でした。日本語イベント名を確認してください。');
          return;
        }
        enInput.value = translated;
        enInput.dispatchEvent(new Event('input', { bubbles: true }));
        setStatus('英語欄へ反映しました。内容を確認して保存してください。');
      } catch (error) {
        setStatus(error instanceof Error ? error.message : 'AI英訳に失敗しました。');
      } finally {
        button.disabled = false;
        button.textContent = 'AIで英訳';
      }
    });
  });

  editor.querySelectorAll('[data-calendar-event-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      const panel = button.closest('[data-calendar-event-panel]');
      const date = panel?.getAttribute('data-calendar-event-panel') || '';
      const status = panel?.querySelector('[data-calendar-event-ai-status]');
      if (!panel || !date) {
        return;
      }

      panel.querySelectorAll('[data-calendar-event-ja], [data-calendar-event-en], .calendar-event-url-field input').forEach((input) => {
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      updateEventBadge(date);
      if (status) {
        status.textContent = 'イベント情報を削除しました。保存すると反映されます。';
      }
    });
  });

  editor.querySelectorAll('[data-calendar-date]').forEach((button) => {
    const date = button.getAttribute('data-calendar-date');
    if (date) {
      dayButtons.set(date, button);
    }
    button.addEventListener('click', () => {
      if (!date) {
        return;
      }
      selected.add(date);
      button.classList.add('is-selected');
      setActiveDate(date);
    });
  });

  editor.querySelectorAll('[data-calendar-clear-selection]').forEach((button) => {
    button.addEventListener('click', () => {
      selected.clear();
      dayButtons.forEach((day) => day.classList.remove('is-selected', 'is-active'));
      setActiveDate('');
    });
  });

  editor.querySelectorAll('[data-calendar-apply-status]').forEach((button) => {
    button.addEventListener('click', () => {
      const status = button.getAttribute('data-calendar-apply-status') || '';
      selected.forEach((date) => {
        const input = editor.querySelector(`[data-calendar-input="${date}"]`);
        const day = editor.querySelector(`[data-calendar-date="${date}"]`);
        if (!input || !day) {
          return;
        }
        input.value = status;
        day.dataset.calendarStatus = status;
        day.classList.remove('status-open', 'status-closed', 'status-am_closed', 'status-pm_closed');
        const visualStatus = status || day.getAttribute('data-calendar-base-status') || 'open';
        if (visualStatus) {
          day.classList.add(`status-${visualStatus}`);
        }
        const label = day.querySelector('[data-calendar-status-label]');
        if (label) {
          label.textContent = status === '' ? labels[visualStatus] || visualStatus : labels[status] || status;
        }
      });
    });
  });
});

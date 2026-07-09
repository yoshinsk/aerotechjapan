<?php
/**
 * httpdocs/app/BusinessCalendar.php
 * 日曜・日本の祝日を基本休業日とし、CMS例外設定を反映した営業日カレンダーを生成します。
 */

declare(strict_types=1);

final class BusinessCalendar
{
    public const STATUS_DEFAULT = '';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_AM_CLOSED = 'am_closed';
    public const STATUS_PM_CLOSED = 'pm_closed';

    public function __construct(private CmsRepository $repo)
    {
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DEFAULT => '基本設定',
            self::STATUS_OPEN => '営業日',
            self::STATUS_CLOSED => '休日',
            self::STATUS_AM_CLOSED => '午前休',
            self::STATUS_PM_CLOSED => '午後休',
        ];
    }

    public function month(int $year, int $month): array
    {
        $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $last = $first->modify('last day of this month');
        $exceptions = $this->repo->businessDayExceptions($first->format('Y-m-d'), $last->format('Y-m-d'));
        $holidays = self::japaneseHolidays((int)$first->format('Y'));
        $days = [];

        for ($day = $first; $day <= $last; $day = $day->modify('+1 day')) {
            $date = $day->format('Y-m-d');
            $baseStatus = $this->defaultStatus($day, $holidays);
            $overrideStatus = $exceptions[$date]['status'] ?? self::STATUS_DEFAULT;
            $status = $overrideStatus !== self::STATUS_DEFAULT ? $overrideStatus : $baseStatus;
            $eventNameJa = trim((string)($exceptions[$date]['event_name_ja'] ?? ''));
            $eventNameEn = trim((string)($exceptions[$date]['event_name_en'] ?? ''));
            $eventUrl = trim((string)($exceptions[$date]['event_url'] ?? ''));
            $days[] = [
                'date' => $date,
                'day' => (int)$day->format('j'),
                'weekday' => (int)$day->format('w'),
                'status' => $status,
                'base_status' => $baseStatus,
                'override_status' => $overrideStatus,
                'holiday_name' => $holidays[$date] ?? '',
                'note_ja' => $exceptions[$date]['note_ja'] ?? '',
                'note_en' => $exceptions[$date]['note_en'] ?? '',
                'event_name_ja' => $eventNameJa,
                'event_name_en' => $eventNameEn,
                'event_url' => $eventUrl,
                'has_event' => $eventNameJa !== '' || $eventNameEn !== '',
                'is_today' => $date === date('Y-m-d'),
            ];
        }

        $events = array_values(array_filter(
            $days,
            static fn(array $day): bool => (bool)$day['has_event']
        ));

        return [
            'year' => (int)$first->format('Y'),
            'month' => (int)$first->format('n'),
            'label' => $first->format('Y年n月'),
            'first_weekday' => (int)$first->format('w'),
            'days' => $days,
            'events' => $events,
        ];
    }

    public function months(int $count = 2, ?DateTimeImmutable $start = null): array
    {
        $start = ($start ?? new DateTimeImmutable('first day of this month'))->modify('first day of this month')->setTime(0, 0);
        $months = [];
        for ($i = 0; $i < $count; $i++) {
            $target = $start->modify("+{$i} month");
            $months[] = $this->month((int)$target->format('Y'), (int)$target->format('n'));
        }
        return $months;
    }

    private function defaultStatus(DateTimeImmutable $date, array $holidays): string
    {
        if ((int)$date->format('w') === 0 || isset($holidays[$date->format('Y-m-d')])) {
            return self::STATUS_CLOSED;
        }
        return self::STATUS_OPEN;
    }

    public static function japaneseHolidays(int $year): array
    {
        $holidays = [
            sprintf('%04d-01-01', $year) => '元日',
            self::nthMonday($year, 1, 2) => '成人の日',
            sprintf('%04d-02-11', $year) => '建国記念の日',
            sprintf('%04d-02-23', $year) => '天皇誕生日',
            sprintf('%04d-03-%02d', $year, self::vernalEquinoxDay($year)) => '春分の日',
            sprintf('%04d-04-29', $year) => '昭和の日',
            sprintf('%04d-05-03', $year) => '憲法記念日',
            sprintf('%04d-05-04', $year) => 'みどりの日',
            sprintf('%04d-05-05', $year) => 'こどもの日',
            self::nthMonday($year, 7, 3) => '海の日',
            sprintf('%04d-08-11', $year) => '山の日',
            self::nthMonday($year, 9, 3) => '敬老の日',
            sprintf('%04d-09-%02d', $year, self::autumnEquinoxDay($year)) => '秋分の日',
            self::nthMonday($year, 10, 2) => 'スポーツの日',
            sprintf('%04d-11-03', $year) => '文化の日',
            sprintf('%04d-11-23', $year) => '勤労感謝の日',
        ];

        ksort($holidays);
        $holidays = self::applySubstituteHolidays($holidays);
        $holidays = self::applyCitizensHolidays($holidays, $year);
        ksort($holidays);
        return $holidays;
    }

    private static function nthMonday(int $year, int $month, int $nth): string
    {
        $date = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        while ((int)$date->format('w') !== 1) {
            $date = $date->modify('+1 day');
        }
        return $date->modify('+' . ($nth - 1) . ' week')->format('Y-m-d');
    }

    private static function vernalEquinoxDay(int $year): int
    {
        return (int)floor(20.8431 + (0.242194 * ($year - 1980)) - floor(($year - 1980) / 4));
    }

    private static function autumnEquinoxDay(int $year): int
    {
        return (int)floor(23.2488 + (0.242194 * ($year - 1980)) - floor(($year - 1980) / 4));
    }

    private static function applySubstituteHolidays(array $holidays): array
    {
        foreach ($holidays as $date => $name) {
            $day = new DateTimeImmutable($date);
            if ((int)$day->format('w') !== 0) {
                continue;
            }
            $substitute = $day->modify('+1 day');
            while (isset($holidays[$substitute->format('Y-m-d')])) {
                $substitute = $substitute->modify('+1 day');
            }
            $holidays[$substitute->format('Y-m-d')] = '振替休日';
        }
        return $holidays;
    }

    private static function applyCitizensHolidays(array $holidays, int $year): array
    {
        $start = new DateTimeImmutable(sprintf('%04d-01-02', $year));
        $end = new DateTimeImmutable(sprintf('%04d-12-30', $year));
        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $date = $day->format('Y-m-d');
            if (isset($holidays[$date]) || (int)$day->format('w') === 0) {
                continue;
            }
            $prev = $day->modify('-1 day')->format('Y-m-d');
            $next = $day->modify('+1 day')->format('Y-m-d');
            if (isset($holidays[$prev], $holidays[$next])) {
                $holidays[$date] = '国民の休日';
            }
        }
        return $holidays;
    }
}

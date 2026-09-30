<?php

namespace App\Actions\Reports;

use App\Actions\Reviews\SubmitReview;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Exceptions\TooManyReports;
use App\Models\Business;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Anyone logged in can report a business, a review, a reply or a public
 * photo. Reports are rate limited and deduplicated per reporter and target.
 */
class SubmitReport
{
    public const MAX_DETAILS = 1000;

    /** Media collections visitors can see (and therefore report). */
    public const PUBLIC_MEDIA_COLLECTIONS = ['gallery', 'logo', 'cover', 'photos'];

    /**
     * @throws TooManyReports
     */
    public function __invoke(User $reporter, Model $target, ReportReason $reason, ?string $details): Report
    {
        $this->assertReportable($target);

        $key = 'reports:'.$reporter->id;

        if (RateLimiter::tooManyAttempts($key, (int) config('reviews.reports_per_hour'))) {
            throw new TooManyReports(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, 3600);

        $existing = Report::query()
            ->where('reportable_type', $target->getMorphClass())
            ->where('reportable_id', $target->getKey())
            ->where('reporter_id', $reporter->id)
            ->where('status', ReportStatus::Open)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $details = SubmitReview::cleanBody($details);

        $report = new Report([
            'reportable_type' => $target->getMorphClass(),
            'reportable_id' => $target->getKey(),
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details === null ? null : mb_substr($details, 0, self::MAX_DETAILS),
        ]);
        $report->forceFill(['status' => ReportStatus::Open])->save();

        return $report;
    }

    private function assertReportable(Model $target): void
    {
        $allowed = match (true) {
            $target instanceof Business => $target->isPublished(),
            $target instanceof Review => $target->isPublished(),
            $target instanceof ReviewReply => true,
            $target instanceof Media => in_array($target->collection_name, self::PUBLIC_MEDIA_COLLECTIONS, true)
                && in_array($target->model_type, ['business', 'review'], true),
            default => false,
        };

        if (! $allowed) {
            throw new InvalidArgumentException('This content cannot be reported.');
        }
    }
}

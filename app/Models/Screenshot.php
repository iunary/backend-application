<?php

namespace App\Models;

use App\Scopes\ScreenshotScope;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Intervention\Image\Constraint;
use Image;
use Storage;

/**
 * @apiDefine ScreenshotObject
 *
 * @apiSuccess {Integer}  screenshot.id                ID
 * @apiSuccess {Integer}  screenshot.time_interval_id  Time interval ID
 * @apiSuccess {String}   screenshot.path              Image url
 * @apiSuccess {String}   screenshot.thumbnail_path    Thumbnail url
 * @apiSuccess {ISO8601}  screenshot.created_at        Creation DateTime
 * @apiSuccess {ISO8601}  screenshot.updated_at        Update DateTime
 * @apiSuccess {ISO8601}  screenshot.deleted_at        Delete DateTime or `NULL` if wasn't deleted
 * @apiSuccess {Object}   screenshot.time_interval     The time interval that screenshot belongs to
 * @apiSuccess {Boolean}  screenshot.important         Important flag
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine ScreenshotParams
 *
 * @apiParam {Integer}  [id]                ID
 * @apiParam {Integer}  [time_interval_id]  Time interval ID
 * @apiParam {String}   [path]              Image url
 * @apiParam {String}   [thumbnail_path]    Thumbnail url
 * @apiParam {ISO8601}  [created_at]        Creation DateTime
 * @apiParam {ISO8601}  [updated_at]        Update DateTime
 * @apiParam {ISO8601}  [deleted_at]        Delete DateTime
 * @apiParam {Object}   [time_interval]     The time interval that screenshot belongs to
 * @apiParam {Boolean}  [important]         Important flag
 *
 * @apiVersion 1.0.0
 */

/**
 * App\Models\Screenshot
 *
 * @property int $id
 * @property int $time_interval_id
 * @property string $path
 * @property string $thumbnail_path
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 * @property bool $is_removed
 * @property TimeInterval $timeInterval
 * @method static bool|null forceDelete()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Screenshot whereCreatedAt($value)
 * @method static EloquentBuilder|Screenshot whereDeletedAt($value)
 * @method static EloquentBuilder|Screenshot whereId($value)
 * @method static EloquentBuilder|Screenshot whereImportant($value)
 * @method static EloquentBuilder|Screenshot whereIsRemoved($value)
 * @method static EloquentBuilder|Screenshot wherePath($value)
 * @method static EloquentBuilder|Screenshot whereThumbnailPath($value)
 * @method static EloquentBuilder|Screenshot whereTimeIntervalId($value)
 * @method static EloquentBuilder|Screenshot whereUpdatedAt($value)
 * @method static EloquentBuilder|Screenshot newModelQuery()
 * @method static EloquentBuilder|Screenshot newQuery()
 * @method static EloquentBuilder|Screenshot query()
 * @method static QueryBuilder|Screenshot withTrashed()
 * @method static QueryBuilder|Screenshot withoutTrashed()
 * @method static QueryBuilder|Screenshot onlyTrashed()
 * @mixin EloquentIdeHelper
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Screenshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Screenshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Screenshot query()
 */
class Screenshot extends Model
{
    use SoftDeletes;

    public const DEFAULT_PATH = 'uploads/static/none.png';

    /**
     * table name from database
     * @var string
     */
    protected $table = 'screenshots';

    /**
     * @var array
     */
    protected $fillable = [
        'time_interval_id',
        'path',
        'thumbnail_path',
        'important',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'time_interval_id' => 'integer',
        'path' => 'string',
        'thumbnail_path' => 'string',
        'important' => 'boolean',
        'is_removed' => 'boolean',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new ScreenshotScope);
    }

    public static function createByInterval(TimeInterval $timeInterval, string $path = self::DEFAULT_PATH): Screenshot
    {
        $screenshot = Image::make(storage_path('app/' . $path));
        $thumbnail = $screenshot->resize(280, null, fn(Constraint $constraint) => $constraint->aspectRatio());
        $thumbnailPath = str_replace(
            'uploads/screenshots',
            'uploads/screenshots/thumbs',
            $path
        );

        Storage::put($thumbnailPath, (string)$thumbnail->encode());

        $screenshotData = [
            'time_interval_id' => $timeInterval->id,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
        ];

        return self::create($screenshotData);
    }

    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }
}

<?php

namespace DaydreamLab\Media\Services\Media\Front;

use DaydreamLab\Media\Helpers\MediaHelper;
use DaydreamLab\Media\Repositories\Media\Front\MediaFrontRepository;
use DaydreamLab\Media\Services\Media\MediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaFrontService extends MediaService
{
    protected $type = 'MediaFront';

    protected $media_storage = null;

    protected $thumb_storage = null;

    protected $media_storage_type = 'media-public';

    protected $thumb_storage_type = 'media-thumb';

    protected $media_path = null;

    protected $media_link_base = '/storage/media';

    protected $thumb_path = null;

    protected $userMerchantID = null;

    public function __construct(MediaFrontRepository $repo, $userMerchantID = '')
    {
        $this->repo = $repo;

        if( config('daydreamlab.media.dddream-merchant-mode') ){
            $this->media_storage_type = 'media-public-merchant';
            $this->thumb_storage_type = 'media-thumb-merchant';
            $this->media_link_base .= '/merchant';

            if ($userMerchantID == '') {
                $user = Auth::guard('api')->user();
                if(!$user) {
                    $this->throwResponse('UserUnauthorized');
                }

                if ($merchant = $user->merchants()->first()){
                    $this->userMerchantID = $merchant->id;
                } else {
                    if (!$user->isSuperUser())
                    {
                        $this->throwResponse('MediaThisAdminUserNotHaveMerchant');
                    }
                }
            } else {
                $this->userMerchantID = $userMerchantID;
            }
        }

        $this->media_storage = Storage::disk($this->media_storage_type);
        $this->thumb_storage = Storage::disk($this->thumb_storage_type);
        $this->media_path    = $this->media_storage->getDriver()->getAdapter()->getPathPrefix();
        $this->thumb_path    = $this->thumb_storage->getDriver()->getAdapter()->getPathPrefix();

        if( config('daydreamlab.media.dddream-merchant-mode') ){
            //Helper::show(Auth::guard('api')->user()->merchants->first()->id);
            if( !$this->media_storage->exists($this->userMerchantID) &&
                !$this->thumb_storage->exists($this->userMerchantID) ){
                //利用merchantID建構Dir
                $result_media   = $this->media_storage->makeDirectory($this->userMerchantID, intval( '0755', 8 ));
                $result_thumb   = $this->thumb_storage->makeDirectory($this->userMerchantID, intval( '0755', 8 ));
            }
            $this->media_path    = $this->media_storage->getDriver()->getAdapter()->getPathPrefix().$this->userMerchantID.'/';
            $this->thumb_path    = $this->thumb_storage->getDriver()->getAdapter()->getPathPrefix().$this->userMerchantID.'/';
        }

    }

    /**
     * 將用戶line發送的檔案上傳保存
     * @param $resource | Upload file from LINE
     * @param string $type mime type ex:image/jpeg
     * @return string
     */
    public function uploadFromLineUser($resource, string $mime)
    {
        $extension = substr($mime, strpos($mime, '/') + 1);
        $filename = Str::random(36) . '.' . $extension;

        \Intervention\Image\Facades\Image::make($resource->getRawBody())->save($this->media_path . $filename);
        \Intervention\Image\Facades\Image::make($resource->getRawBody())->fit(200)->save($this->thumb_path .$filename);

        // 回傳特殊自定義格式觸發linebot事件
        $type = substr($mime, 0, strpos($mime, '/'));
        $path = $this->media_link_base . '/' . $this->userMerchantID . '/' . $filename;
        $userSendText = '[TemplateMsg][File]_' . $type . '_' . $path;

        return $userSendText;
    }
}

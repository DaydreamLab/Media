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

    public function __construct(MediaFrontRepository $repo, $userMerchantId = '')
    {
        $this->repo = $repo;

        if( config('daydreamlab.media.dddream-merchant-mode') ){
            $this->media_storage_type = 'media-public-merchant';
            $this->thumb_storage_type = 'media-thumb-merchant';
            $this->media_link_base .= '/merchant';

            $user = Auth::guard('api')->user();
            if(!$user) {
                $this->throwResponse('UserUnauthorized');
            }

            if ($merchant = $user->merchants()->first()){
                $this->userMerchantID = $merchant->id;
            } else if ($userMerchantId !== '') {
                $this->userMerchantID = $userMerchantId;
            } else {
                if (!$user->isSuperUser())
                {
                    $this->throwResponse('MediaThisAdminUserNotHaveMerchant');
                }
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
     *
     * @param $resource
     * @param string $type | mime
     * @param string $dir
     * @return string
     */
    public function uploadFromLineUser(resource $resource, string $mime, string $dir = '')
    {
        $extension = substr($mime, strpos($mime, '/') + 1);
        $filename = Str::random(36) . '.' . $extension;

        \Intervention\Image\Facades\Image::make($resource->getRawBody())->save($this->media_path . $filename);
        \Intervention\Image\Facades\Image::make($resource->getRawBody())->fit(200)->save($this->thumb_path .$filename);

        $path = substr($this->media_path, strpos($this->media_path, '/storage')) . '/' . $filename;
        $type = substr($mime, 0, strpos($mime, '/'));

        // 回傳特殊自定義格式觸發linebot事件
        $userSendText = '[TemplateMsg][File]_' . $type . '_' . $path;

        return $userSendText;
    }
}

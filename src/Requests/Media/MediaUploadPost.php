<?php

namespace DaydreamLab\Media\Requests\Media;

use DaydreamLab\JJAJ\Requests\AdminRequest;
use DaydreamLab\Media\Helpers\MediaHelper;

class MediaUploadPost extends AdminRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return parent::authorize();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $media_config = MediaHelper::getMediaConfig();
        return [
            'files'     => 'required|array',
            'files.*'   => 'nullable|max:'.$media_config['media_upload_limit'],
            'dir'       => 'required|string',
        ];
    }
}

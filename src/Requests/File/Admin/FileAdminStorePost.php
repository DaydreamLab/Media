<?php

namespace DaydreamLab\Media\Requests\File\Admin;

use DaydreamLab\JJAJ\Helpers\Helper;
use DaydreamLab\JJAJ\Helpers\InputHelper;
use DaydreamLab\JJAJ\Requests\AdminRequest;
use DaydreamLab\Media\Helpers\MediaHelper;
use Illuminate\Validation\Rule;

class FileAdminStorePost extends AdminRequest
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
            'id'            => 'nullable|integer',
            'name'          => 'required|string',
            'category_id'   => 'nullable|integer',
            'state'         => [
                'nullable',
                'integer',
                Rule::in([0,1,-2])
            ],
            'introtext'     => 'nullable|string',
            'description'   => 'nullable|string',
            'access'        => 'nullable|integer',
            'ordering'      => 'nullable|integer',
            'file'          => 'nullable|max:'.$media_config['upload_limit'],
            'password'      => 'nullable|max:16|min:8',
            'groupIds'      => 'nullable|array',
            'groupIds.*'    => 'nullable|integer',
        ];
    }


    public function validated()
    {
        $validated = parent::validated();

        $validated->put('category_id', $validated->get('categoryId'));
        $validated->put('contentType', $this->file->getMimeType());
        $validated->put('extension', $this->file->extension());
        $validated->put('size', ceil((double) ($this->file->getSize() / 1024)));
        if (InputHelper::null($validated, 'password')) {
            $validated->put('password', bcrypt($validated->get('password')));
        }

        $validated->forget('categoryId');

        return $validated;
    }
}

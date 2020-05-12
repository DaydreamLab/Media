<?php

namespace DaydreamLab\Media\Helpers;

use Carbon\Carbon;
use DaydreamLab\JJAJ\Helpers\Helper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class MediaHelper
{
    public static function appendMeta($items, $type, $dir, $storage)
    {
        $result = new Collection();

        foreach ($items as $item)
        {
            $folders = explode('/',$item);
            $temp['name'] = $folders[count($folders)-1];

            if ($type == 'folder')
            {
                $temp['path'] = '';
                //if config merchant mode = 1
                if( mb_strlen( $folders[0], "utf-8") == 20 ){
                    unset($folders[0]);
                    foreach ($folders as $itemPath){
                        $temp['path'].= '/'.$itemPath;
                    }
                }else{
                    //if config merchant mode = 0
                    $temp['path'] = '/'.$item;
                }

                $temp['type']   = 'Folder';
                $temp['size']   = null;
                $temp['url']    = null;
                $temp['thumb']  = env('APP_URL') . Storage::url('media/thumbs/') . 'icons/folder.png';

            }
            else
            {
                $temp['path'] = '/'.$item;
                $temp['type']       = $storage->mimeType($item);
                $temp['size']       = round($storage->size($item)/1024) . ' KB';
                $dot_pos            = strrpos($temp['name'],'.');
                $file_type          = Str::substr($temp['name'], $dot_pos + 1);
                $temp['extension']  = $file_type;
                $temp['url']        = $storage->url($item);
                //$temp['thumb']      = env('APP_URL') . Storage::url('media/thumbs') . '/'. substr($dir, 1) . '/' .  $temp['name'];
                $temp['thumb']      = self::getThumbPath($dir, $temp['name'], $file_type, $temp['type'] );
            }
            $temp['modified'] = Carbon::createFromTimestamp($storage->lastModified($item))->toDateTimeString();
            $temp['children'] = [];


            $result->push($temp) ;
        }

        return $result;
    }


    public static function filterDirectories($all)
    {
        $data = [];
        foreach ($all as $directory)
        {
            $temp = explode('/', $directory);
            if($temp[0] != 'thumbs')
            {
                $data[] = $directory;
            }
        }

        return $data;
    }


    public static function getDirPath($path)
    {
        if(mb_strpos($path, '/', 0, 'UTF8') == 0){
            $dir = substr($path, 1);
        }else{
            $dir = substr($path, 0);
        }
        $dir = $dir . '/';

        return $dir;
    }


    public static function getFileExtension($name)
    {
        //$dot_pos = strpos($name,'.');
        $dot_pos = mb_strrpos($name, '.', 'UTF8');

        return Str::substr($name,  $dot_pos+1);
    }


    public static function getFileName($name)
    {
        //$dot_pos = strrpos($name,'.');
        $dot_pos = mb_strrpos($name, '.', 'UTF8');

        return Str::substr($name, 0, $dot_pos);
    }


    public static function getThumbPath($dir, $name, $extension, $mime)
    {
        $thumb_path = env('APP_URL') . Storage::url('media/thumbs/');

        if (in_array($mime, config('media.mime.image')))
        {
            if(mb_strpos($dir, '/', 0, 'UTF8') == 0){
                $dir = substr($dir, 1);
            }else{
                $dir = substr($dir, 0);
            }

            return $thumb_path . $dir . '/' . $name;
        }
        elseif (in_array($mime, config('media.mime.application')))
        {
            return $thumb_path . 'icons/' . $extension . '-icon.png';
        }
        elseif (in_array($mime, config('media.mime.text')))
        {
            return $thumb_path . 'icons/' . 'file-icon.png';
        }
        else
        {
            return null;
        }
    }

    public static function getDiskPath($disk)
    {
        return Storage::disk($disk)->getAdapter()->getPathPrefix();
    }


    public static function isImage($name)
    {
        return in_array( self::getFileExtension($name), config('media.extension.image')) ? true : false;
    }


    public static function getMediaConfig()
    {
        if (file_exists(config_path('media.php'))) {
            $media = config('media');
        } else {
            $media = require __DIR__. '/../Configs/media.php';
        }

        return $media;
    }

}
<?php

namespace EscapeWork\LaraMedias\Traits;

use EscapeWork\LaraMedias\Collections\MediaCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait Medias
{

    protected $media;

    public function uploadSingleMedia(UploadedFile $media, $field)
    {
        $config  = config('medias.models.' . $this->getTable());
        $dir     = config('medias.dir') . '/' . $this->getTable();
        $uploads = $this->upload()->to($dir)->execute($media);
        $medias  = $this->resizeMedias($uploads, $dir);

        if (! is_null($this->{$field})) {
            $this->removeSingleMedia($config, $dir, $field);
        }

        $this->{$field} = $medias->first();
        return $this->{$field};
    }

    public function removeSingleMedia($config, $dir, $field)
    {
        $destroyer = $this->mediaDestroyerService();

        $destroyer->removeFromModel($this, $config, $dir, $field);
    }

    public function uploadMultipleMedias($medias)
    {
        if (! $this->areMediasValid($medias)) {
            return;
        }

        $dir     = config('medias.dir') . '/' . config('medias.path');
        $uploads = $this->upload()->to($dir)->execute($medias);
        $files   = $this->resizeMedias($uploads, $dir);

        return $this->mediaService()->to($this)->save($files);
    }

    public function removeMedias()
    {
        $ids = $this->medias->lists('id');

        return $this->detachMedias($ids);
    }

    public function detachMedias($ids = [])
    {
        if (is_null($ids) || count($ids) == 0) {
            return;
        }

        return $this->mediaDestroyerService()->removeSpecificMedias($ids);
    }

    protected function resizeMedias($files, $dir)
    {
        $files = new MediaCollection($files);
        $files->resize($dir);

        return $files;
    }

    protected function upload()
    {
        return app('EscapeWork\LaravelUploader\Upload');
    }

    protected function areMediasValid($medias)
    {
        if ($medias instanceof UploadedFile) {
            return true;
        }

        return is_array($medias) && count($medias) > 0 && $medias[0] != null;
    }

    protected function mediaDestroyerService()
    {
        return app('EscapeWork\LaraMedias\Services\MediasDestroyerService');
    }

    protected function mediaService()
    {
        return app('EscapeWork\LaraMedias\Services\MediaService');
    }
}

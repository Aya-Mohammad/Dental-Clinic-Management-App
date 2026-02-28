<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasImageActions
{
    /**
     * Add image to a model using a specified image model and folder.
     */
    public function addImage(Model $model, UploadedFile $file, string $imageModelClass, string $foreignKey, string $folder = 'uploads'): Model
    {
        $path = $file->store($folder, 'public');

        $image = $imageModelClass::create([
            'path' => $path,
            $foreignKey => $model->id,
        ]);

        return $image;
    }

    public function addImageFromPath(Model $model, string $path, string $imageModelClass, string $foreignKey): Model
    {
        $image = $imageModelClass::create([
            'path' => $path,
            $foreignKey => $model->id,
        ]);

        return $image;
    }

    /**
     * Delete an image by its ID using specified image model.
     */
    public function deleteImageById(int $imageId, string $imageModelClass): bool
    {
        $image = $imageModelClass::findOrFail($imageId);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return true;
    }

    /**
     * Get images related to the model using the given relation name.
     */
    public function getImagesFor(Model $model, string $relationName): array
    {
        return $model->{$relationName}->toArray();
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // Image envoyée (profil, animation, partenaire, salle) : seulement une vraie image (contenu vérifié,
    // pas de SVG qui peut contenir du script), 10 Mo au plus, enregistrée sous un nom choisi par le serveur
    // dans public/images/<dossier>. Renvoie le nom du fichier ; refus 422 sinon.
    protected function savePicture(UploadedFile $file, string $folder): string
    {
        Validator::make(
            ['picture' => $file],
            ['picture' => 'file|mimes:jpg,jpeg,png,gif,webp|max:10240'],
            [
                'picture.mimes' => "L'image doit être au format JPG, PNG, GIF ou WebP.",
                'picture.max'   => "L'image ne doit pas dépasser 10 Mo.",
                'picture.file'  => "L'image n'a pas pu être envoyée.",
            ]
        )->validate();

        // L'extension vient du contenu du fichier, pas du nom donné par l'utilisateur
        $filename = uniqid() . '.' . $file->extension();
        $file->move(public_path("images/$folder/"), $filename);

        return $filename;
    }
}

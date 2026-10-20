<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandingLogoController extends Controller
{
    public function __invoke(BrandingService $branding): StreamedResponse
    {
        $path = $branding->logoPath();

        abort_if($path === null, 404);

        $disk = Storage::disk(BrandingService::LOGO_DISK);

        return $disk->response($path, null, [
            'Content-Type' => $disk->mimeType($path) ?: 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:publish-vehicle-photos-to-r2')]
#[Description('Upload the bundled, credited vehicle photos to the configured Cloudflare R2 bucket')]
class PublishVehiclePhotosToR2 extends Command
{
    public function handle(): int
    {
        if (! config('filesystems.disks.r2.bucket') || ! config('filesystems.disks.r2.endpoint') || ! config('filesystems.disks.r2.key') || ! config('filesystems.disks.r2.secret')) {
            $this->error('Set R2_BUCKET, R2_ENDPOINT, R2_ACCESS_KEY_ID and R2_SECRET_ACCESS_KEY before publishing.');

            return self::FAILURE;
        }

        foreach (array_keys(config('auction_photos')) as $photoKey) {
            $localPath = public_path('images/vehicles/'.$photoKey.'.jpg');
            $stream = fopen($localPath, 'rb');

            if ($stream === false) {
                $this->error('Cannot read '.$localPath);

                return self::FAILURE;
            }

            try {
                Storage::disk('r2')->put('vehicles/'.$photoKey.'.jpg', $stream, ['ContentType' => 'image/jpeg']);
            } finally {
                fclose($stream);
            }

            $this->info('Published vehicles/'.$photoKey.'.jpg');
        }

        $this->info('Set R2_PUBLIC_URL to the bucket public URL, then clear configuration cache.');

        return self::SUCCESS;
    }
}

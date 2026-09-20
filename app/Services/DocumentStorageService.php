<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Sport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    private const EXTENSIONS = ['pdf', 'md', 'markdown'];

    /**
     * @return array<int, string>
     */
    public function sessionDirectories(Session $session): array
    {
        return $this->uniqueDirectories([
            'documents/'.$session->organization_id.'/'.$session->id.'/general',
            'documents/'.($session->organization?->slug ?? $session->organization_id).'/'.$session->id.'/general',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function sportDirectories(Sport $sport, string $sessionId): array
    {
        return $this->uniqueDirectories([
            'documents/'.$sport->organization_id.'/'.$sessionId.'/sports/'.$sport->id,
            'documents/'.($sport->organization?->slug ?? $sport->organization_id).'/'.$sessionId.'/sports/'.$sport->slug,
        ]);
    }

    public function sessionDirectory(Session $session): string
    {
        return 'documents/'.$session->organization_id.'/'.$session->id.'/general';
    }

    public function sportDirectory(Sport $sport, string $sessionId): string
    {
        return 'documents/'.$sport->organization_id.'/'.$sessionId.'/sports/'.$sport->id;
    }

    /**
     * @param  array<int, string>  $directories
     * @return Collection<int, array{name: string, path: string}>
     */
    public function availableFiles(array $directories): Collection
    {
        return collect($directories)
            ->flatMap(fn (string $directory) => Storage::disk('public')->files($directory))
            ->filter(fn (string $path) => $this->isSupportedPath($path))
            ->unique()
            ->sort()
            ->map(fn (string $path) => [
                'name' => pathinfo($path, PATHINFO_BASENAME),
                'path' => $path,
            ])
            ->values();
    }

    /**
     * A user-supplied path is selectable only if it is a supported file below
     * one of the directories belonging to the current organization/session.
     * Reject traversal segments before asking the filesystem for existence.
     *
     * @param  array<int, string>  $directories
     */
    public function isSelectable(string $path, array $directories): bool
    {
        $normalizedPath = $this->normalize($path);
        if ($normalizedPath === '' || str_contains($normalizedPath, '..') || ! $this->isSupportedPath($normalizedPath)) {
            return false;
        }

        foreach ($directories as $directory) {
            $normalizedDirectory = $this->normalize($directory);
            if (str_starts_with($normalizedPath, $normalizedDirectory.'/')) {
                return Storage::disk('public')->exists($normalizedPath);
            }
        }

        return false;
    }

    private function isSupportedPath(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    /**
     * @param  array<int, string>  $directories
     * @return array<int, string>
     */
    private function uniqueDirectories(array $directories): array
    {
        return collect($directories)->map(fn (string $directory) => $this->normalize($directory))->unique()->values()->all();
    }

    private function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}

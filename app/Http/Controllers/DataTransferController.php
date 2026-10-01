<?php

namespace App\Http\Controllers;

use App\Models\DataTransfer;
use App\Services\DataTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DataTransferController extends Controller
{
    public function show(Request $request, string $id, DataTransferService $transfers): JsonResponse
    {
        $transfer = $this->forCurrentOrganization($request, $id);

        Gate::authorize('view', $transfer);

        return response()->json(['data' => $transfers->status($transfer)]);
    }

    public function download(Request $request, string $id)
    {
        $transfer = $this->forCurrentOrganization($request, $id);

        Gate::authorize('download', $transfer);

        abort_unless($transfer->status === DataTransfer::STATUS_COMPLETED, 409, 'The transfer is not ready for download.');
        abort_unless($transfer->output_path && Storage::disk('local')->exists($transfer->output_path), 404);
        abort_unless(str_starts_with($transfer->output_path, 'transfers/'.$transfer->organization_id.'/output/'.$transfer->id.'/')
            && ! str_contains($transfer->output_path, '..') && ! str_contains($transfer->output_path, '\\'), 404);

        return Storage::disk('local')->download(
            $transfer->output_path,
            $transfer->output_name ?: basename($transfer->output_path),
        );
    }

    private function forCurrentOrganization(Request $request, string $id): DataTransfer
    {
        abort_unless($request->user()->organization_id, 404);

        return DataTransfer::forOrganization($request->user()->organization_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}

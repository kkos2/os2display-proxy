<?php

namespace App\Http\Controllers;

use App\Models\RequestResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use function FluidXml\fluidxml;

/**
 * Controller responsible for storing incoming POST requests and returning
 * the stored request when the same path is requested with GET.
 */
class RequestResponseController extends Controller
{

    public function get(Request $request): Response {
        $now = Carbon::createFromTimestamp(LARAVEL_START);

        $requestResponse = RequestResponse::where('path', $request->path())
            ->where(function ($query) use ($request) {
                // If the request accepts content types (except for the */*
                // wildcard then only retrieve matching responses.
                $contentTypes = $request->getAcceptableContentTypes();
                if (!empty($contentTypes) &&
                    !in_array('*/*', $contentTypes)) {
                    $query->whereIn('content_type', $contentTypes);
                }
            })
            ->firstOrFail();

        $data = $requestResponse->data;
        // The caller has requested a specific display/skærm.
        // Remove items which do not specify belonging to that specific display.
        if ($request->get('display')) {
            // We use FluidXML and XPath for querying and DOM manipulation so
            // this is only support for XML responses.
            if ($requestResponse->content_type !== 'application/xml') {
                return \response('Display filtering is only supported for XM responses', 400);
            }

            $data = fluidxml($data)
                ->remove("/result/item[ not( .//skærme/item[ text() = '{$request->get('display')}' ] ) ]")
                ->xml();
        }

        return response($data)
            ->header('Content-Type', $requestResponse->content_type)
            // Add the age header to help determine when an item was last
            // updated. This might help debugging.
            ->header('Age', $now->diffInSeconds($requestResponse->updated_at));
    }

    public function create(Request $request): Response {
        RequestResponse::updateOrCreate(
            [
                'path' => $request->path(),
                'content_type' => $request->header('Content-Type'),
            ],
            [
                'path' => $request->path(),
                'content_type' => $request->header('Content-Type'),
                'data' => $request->getContent(),
            ]
        );

        return response(null, 201);
    }

}

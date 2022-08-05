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
        // The caller has requested a specific display/skærm or
        // place/field_os2_house_list.
        // Remove items which do not meet the filter.
        if ($request->get('display') || $request->get('place')) {
            // We use FluidXML and XPath for querying and DOM manipulation so
            // this is only support for XML responses.
            if ($requestResponse->content_type !== 'application/xml') {
                return \response('Filtering is only supported for XM responses', 400);
            }

            $xml = fluidxml($data);
            if ($request->get('display')) {
                // Events and service spots uses different field names to show
                // which displays they belong to. Account for both.
                $xml = $xml->remove("/result/item[
                    not(
                        ./skærme/item[ text() = '{$request->get('display')}' ] or
                        ./field_os2_display_list_spot/item[ text() = '{$request->get('display')}' ]
                    )
                ]");
            }
            if ($request->get('place')) {
                $xml = $xml->remove("/result/item[
                    not(
                        .//field_display_institution/item[ text() = '{$request->get('place')}' ]
                    )
                ]");
            }
            $data = $xml->xml();
        }

        if ($request->path() === 'events' &&
            $requestResponse->content_type == 'application/xml') {
            $xml = fluidxml($data);
            $items = $xml->query('/result/item')->array();
            usort($items, function ($a, $b) {
               $aDate = self::startdateFromEventXml($a);
               $bDate = self::startdateFromEventXml($b);
               return $aDate->getTimestamp() - $bDate->getTimestamp();
            });

            // Remove existing and readd sorted events.
            $xml = $xml->remove('/result/item');
            array_map(function ($item) use ($xml) {
               $xml->query('/result')->add($item);
            }, $items);

            $data = $xml->xml();
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

    private function startdateFromEventXml(\DOMElement $element): \DateTimeInterface {
        $xml = fluidxml($element);
        $date = $xml->query('/item/startdate/item')->current()->nodeValue;
        return Carbon::createFromFormat('d.m.Y', $date);
    }

}

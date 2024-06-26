<?php

namespace SmartyStudio\SmartyTerminal\Http\Controllers;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use SmartyStudio\SmartyTerminal\Console\Kernel;

class TerminalController extends Controller
{
    /**
     * @param Kernel $kernel
     * @param Request $request
     * @param ResponseFactory $responseFactory
     * @param string $view
     * @return Response
     * @throws Exception
     */
    public function index(Kernel $kernel, Request $request, ResponseFactory $responseFactory, string $view = 'index'): Response
    {
        $token = $request->hasSession() ? $request->session()->token() : '';
        $kernel->call('list --ansi');

        $options = json_encode(array_merge($kernel->getConfig(), ['csrfToken' => $token, 'helpInfo' => $kernel->output()]));
        $id = ($view === 'panel') ? Str::random(30) : null;

        return $responseFactory->view('terminal::' . $view, compact('options', 'id'));
    }

    /**
     * @param Kernel $kernel
     * @param Request $request
     * @param ResponseFactory $responseFactory
     * @return JsonResponse
     * @throws Exception
     */
    public function endpoint(Kernel $kernel, Request $request, ResponseFactory $responseFactory): JsonResponse
    {
        $code = $kernel->call($request->get('method'), $request->get('params', []));

        $attributes = $code === 0
            ? [
                'jsonrpc' => $request->get('jsonrpc'),
                'id' => $request->get('id'),
                'result' => $kernel->output()
            ]
            : [
                'jsonrpc' => $request->get('jsonrpc'),
                'id' => null,
                'error' => [
                    'code' => -32600,
                    'message' => 'Invalid Request',
                    'data' => $kernel->output()
                ]
            ];

        return $responseFactory->json($attributes);
    }

    /**
     * @param Request $request
     * @param ResponseFactory $responseFactory
     * @param Filesystem $files
     * @param string $file
     * @return Response
     */
    public function media(Request $request, ResponseFactory $responseFactory, Filesystem $files, string $file): Response
    {
        $filename = __DIR__ . '/resources/assets/dist/css/' . $file;
        $mimeType = str_contains($filename, '.css') ? 'text/css' : 'application/javascript';
        $lastModified = $files->lastModified($filename);
        $eTag = sha1_file($filename);
        $headers = [
            'content-type' => $mimeType,
            'last-modified' => date('D, d M Y H:i:s ', $lastModified) . 'GMT',
        ];

        if (@strtotime($request->server('HTTP_IF_MODIFIED_SINCE')) === $lastModified ||
            trim($request->server('HTTP_IF_NONE_MATCH'), '"') === $eTag
        ) {
            $response = $responseFactory->make(null, 304, $headers);
        } else {
            $response = $responseFactory->stream(function () use ($filename) {
                $out = fopen('php://output', 'wb');
                $file = fopen($filename, 'rb');
                stream_copy_to_stream($file, $out, filesize($filename));
                fclose($out);
                fclose($file);
            }, 200, $headers);
        }

        return $response->setEtag($eTag);
    }
}

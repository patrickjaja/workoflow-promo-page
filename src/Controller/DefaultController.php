<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    public function proxyAvatar(string $encodedUrl): Response
    {
        $avatarUrl = base64_decode($encodedUrl);
        
        if (!filter_var($avatarUrl, FILTER_VALIDATE_URL) || !str_contains($avatarUrl, 'googleusercontent.com')) {
            throw $this->createNotFoundException('Invalid avatar URL');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (compatible; Avatar Proxy)',
                ],
                'timeout' => 10,
            ],
        ]);

        $imageData = @file_get_contents($avatarUrl, false, $context);
        
        if ($imageData === false) {
            throw $this->createNotFoundException('Avatar not found');
        }

        $response = new Response($imageData);
        
        $contentType = 'image/jpeg';
        if (function_exists('getimagesizefromstring')) {
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo && isset($imageInfo['mime'])) {
                $contentType = $imageInfo['mime'];
            }
        }
        
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
        
        return $response;
    }
}
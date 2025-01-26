<?php

namespace App\Controllers\Legal;

use Core\Controller\BaseController;
use Core\Http\Request;
use Core\Http\Response;

class LegalController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        return $this->redirect('legal.mentions');
    }

    public function mentions(Request $request, Response $response): Response
    {
        return $this->render('legal/mentions', [
            'pageTitle' => 'Mentions légales'
        ]);
    }

    public function privacy(Request $request, Response $response): Response
    {
        return $this->render('legal/confidentialite', [
            'pageTitle' => 'Politique de confidentialité'
        ]);
    }

    public function faq(Request $request, Response $response): Response
    {
        return $this->render('legal/faq', [
            'pageTitle' => 'Foire aux questions (FAQ)'
        ]);
    }
} 
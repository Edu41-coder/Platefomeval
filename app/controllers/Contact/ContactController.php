<?php

namespace App\Controllers\Contact;

use Core\Controller\BaseController;
use App\Models\Contact;
use App\Services\ContactService;
use App\Services\EmailService;
use Core\Session\FlashMessage;
use Core\Http\Request;
use Core\Http\Response;
use App\Traits\Controller\RoleCheckTrait;
use App\Security\CsrfToken;

class ContactController extends BaseController
{
    use RoleCheckTrait;

    public function show(Request $request, Response $response): Response
    {
        return $this->index($request, $response);
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->render('contact/index', [
            'pageTitle' => 'Contact',
            'csrfToken' => CsrfToken::getToken()
        ]);
    }

    public function send(Request $request, Response $response): Response
    {
        // Validate CSRF token
        if (!isset($request->post()['_token']) || !CsrfToken::verify($request->post()['_token'])) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirect('contact');
        }

        $data = $request->post();
        
        // Validation rules
        $rules = [
            'name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email'],
            'subject' => ['required', 'string'],
            'message' => ['required', 'string', 'min:10']
        ];

        // Validate the data
        if (!$this->validateData($data, $rules)) {
            return $this->redirect('contact');
        }

        // TODO: Implement email sending
        $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');

        return $this->redirect('contact');
    }

    protected function validateData(array $data, array $rules): bool
    {
        foreach ($rules as $field => $fieldRules) {
            if (in_array('required', $fieldRules) && empty($data[$field])) {
                $this->addFlash('error', "Le champ {$field} est requis.");
                return false;
            }
            if (in_array('email', $fieldRules) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', "L'adresse email n'est pas valide.");
                return false;
            }
            if (in_array('string', $fieldRules) && !is_string($data[$field])) {
                $this->addFlash('error', "Le champ {$field} doit être une chaîne de caractères.");
                return false;
            }
            foreach ($fieldRules as $rule) {
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (strlen($data[$field]) < $min) {
                        $this->addFlash('error', "Le champ {$field} doit contenir au moins {$min} caractères.");
                        return false;
                    }
                }
            }
        }
        return true;
    }
} 
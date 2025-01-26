<?php

namespace App\Services;

use App\Interfaces\Service\ServiceInterface;
use Core\Exception\ServiceException;

/**
 * Classe abstraite de base pour les services
 * 
 * @template T
 * @implements ServiceInterface<T>
 */
abstract class AbstractService implements ServiceInterface
{
    /**
     * Erreurs de validation
     *
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * Règles de validation par défaut
     *
     * @var array<string, array>
     */
    protected array $rules = [];

    /**
     * {@inheritDoc}
     */
    abstract public function get(int $id): ?object;

    /**
     * {@inheritDoc}
     */
    abstract public function getAll(): array;

    /**
     * {@inheritDoc}
     */
    abstract public function create(array $data): object;

    /**
     * {@inheritDoc}
     */
    abstract public function update(int $id, array $data): object;

    /**
     * {@inheritDoc}
     */
    abstract public function delete(int $id): bool;

    /**
     * {@inheritDoc}
     */
    public function validate(array $data, array $rules = []): bool
    {
        $this->clearErrors();
        
        // Utilise les règles fournies ou les règles par défaut
        $validationRules = !empty($rules) ? $rules : $this->rules;
        
        foreach ($validationRules as $field => $fieldRules) {
            if (!isset($data[$field]) && in_array('required', $fieldRules)) {
                $this->addError($field, "Le champ $field est requis");
                continue;
            }

            if (isset($data[$field])) {
                $value = $data[$field];

                foreach ($fieldRules as $rule) {
                    if (is_string($rule)) {
                        switch ($rule) {
                            case 'required':
                                if (empty($value)) {
                                    $this->addError($field, "Le champ $field ne peut pas être vide");
                                }
                                break;

                            case 'email':
                                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                    $this->addError($field, "L'email n'est pas valide");
                                }
                                break;

                            // Ajoutez d'autres règles de validation ici
                        }
                    } elseif (is_array($rule)) {
                        $ruleName = key($rule);
                        $ruleValue = current($rule);

                        switch ($ruleName) {
                            case 'min':
                                if (strlen($value) < $ruleValue) {
                                    $this->addError($field, "Le champ $field doit contenir au moins $ruleValue caractères");
                                }
                                break;

                            case 'max':
                                if (strlen($value) > $ruleValue) {
                                    $this->addError($field, "Le champ $field ne peut pas dépasser $ruleValue caractères");
                                }
                                break;

                            // Ajoutez d'autres règles avec paramètres ici
                        }
                    }
                }
            }
        }

        // Vérification de la correspondance des mots de passe
        if (isset($data['password']) && isset($data['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $this->addError('password_confirm', 'Les mots de passe ne correspondent pas');
        }

        return empty($this->errors);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(int $id): bool
    {
        return $this->get($id) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * {@inheritDoc}
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Ajoute une erreur de validation
     *
     * @param string $field
     * @param string $message
     */
    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Vérifie si une valeur est vide (null, chaîne vide, tableau vide)
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Nettoie une chaîne de caractères
     *
     * @param string $value
     * @return string
     */
    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Vérifie si une chaîne est un email valide
     *
     * @param string $email
     * @return bool
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
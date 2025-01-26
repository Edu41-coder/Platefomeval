<?php

namespace Core\Validator;

use Core\Database\Database;
use Core\Exception\ValidatorException;

class Validator
{
    /**
     * Instance de la base de données
     */
    private Database $db;

    /**
     * Messages d'erreur personnalisés
     */
    private array $messages = [
        'required' => 'Le champ :attribute est requis',
        'email' => 'Le champ :attribute doit être une adresse email valide',
        'min' => 'Le champ :attribute doit être supérieur ou égal à :min',
        'max' => 'Le champ :attribute doit être inférieur ou égal à :max',
        'between' => 'Le champ :attribute doit être compris entre :min et :max',
        'numeric' => 'Le champ :attribute doit être un nombre',
        'integer' => 'Le champ :attribute doit être un nombre entier',
        'string' => 'Le champ :attribute doit être une chaîne de caractères',
        'array' => 'Le champ :attribute doit être un tableau',
        'boolean' => 'Le champ :attribute doit être un booléen',
        'in' => 'Le champ :attribute est invalide',
        'unique' => 'Cette valeur est déjà utilisée',
        'confirmed' => 'La confirmation ne correspond pas',
        'date' => 'Le champ :attribute n\'est pas une date valide',
        'alpha' => 'Le champ :attribute ne doit contenir que des lettres',
        'alpha_num' => 'Le champ :attribute ne doit contenir que des lettres et des chiffres',
        'url' => 'Le champ :attribute doit être une URL valide',
        'ip' => 'Le champ :attribute doit être une adresse IP valide',
        'exists' => 'La valeur sélectionnée pour :attribute est invalide',
        'sometimes' => 'Le champ :attribute est optionnel'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Valide les données selon les règles spécifiées
     *
     * @param array $data Données à valider
     * @param array $rules Règles de validation
     * @throws ValidatorException
     * @return array Données validées
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            // Gestion de la validation des tableaux (ex: notes.*)
            if (strpos($field, '.*') !== false) {
                $arrayField = str_replace('.*', '', $field);
                if (isset($data[$arrayField]) && is_array($data[$arrayField])) {
                    foreach ($data[$arrayField] as $key => $value) {
                        try {
                            $this->validateValue("{$arrayField}.{$key}", $value, $fieldRules, $data);
                            $validated[$arrayField][$key] = $value;
                        } catch (\Exception $e) {
                            if (!isset($errors["{$arrayField}.{$key}"])) {
                                $errors["{$arrayField}.{$key}"] = [];
                            }
                            $errors["{$arrayField}.{$key}"][] = $e->getMessage();
                        }
                    }
                    continue;
                }
            }

            // Validation des champs normaux
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $value = $data[$field] ?? null;
            try {
                $this->validateValue($field, $value, $fieldRules, $data);
                $validated[$field] = $value;
            } catch (\Exception $e) {
                if (!isset($errors[$field])) {
                    $errors[$field] = [];
                }
                $errors[$field][] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new ValidatorException($errors);
        }

        return $validated;
    }

    protected function validateValue(string $field, $value, array $rules, array $data): void
    {
        $isOptional = false;
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                [$ruleName, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
            } else {
                $ruleName = $rule;
                $parameter = null;
            }

            if ($ruleName === 'sometimes') {
                $isOptional = true;
                continue;
            }

            if ($value === null && $isOptional) {
                continue;
            }

            if ($ruleName === 'required' || $value !== null) {
                $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $parameter, $data);
                }
            }
        }
    }

    /**
     * Valide si le champ est requis
     */
    protected function validateRequired(string $field, $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            throw new \Exception($this->messages['required']);
        }
    }

    /**
     * Valide si le champ est un email
     */
    protected function validateEmail(string $field, $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception($this->messages['email']);
        }
    }

    /**
     * Valide la longueur minimale
     */
    protected function validateMin(string $field, $value, $parameter): void
    {
        if (strlen($value) < $parameter) {
            throw new \Exception($this->messages['min']);
        }
    }

    /**
     * Valide la longueur maximale
     */
    protected function validateMax(string $field, $value, $parameter): void
    {
        if (strlen($value) > $parameter) {
            throw new \Exception($this->messages['max']);
        }
    }

    /**
     * Valide si la valeur est unique dans la table
     */
    protected function validateUnique(string $field, $value, $parameter): void
    {
        [$table, $column, $except] = array_pad(explode(',', $parameter), 3, null);
        $column = $column ?? $field;

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
        $params = ['value' => $value];

        if ($except) {
            $sql .= " AND id != :except";
            $params['except'] = $except;
        }

        $result = $this->db->fetchOne($sql, $params);

        if ($result['count'] > 0) {
            throw new \Exception($this->messages['unique']);
        }
    }

    /**
     * Valide si la valeur est dans une liste
     */
    protected function validateIn(string $field, $value, $parameter): void
    {
        $allowedValues = explode(',', $parameter);
        if (!in_array($value, $allowedValues)) {
            throw new \Exception($this->messages['in']);
        }
    }

    /**
     * Valide si la valeur est un booléen
     */
    protected function validateBoolean(string $field, $value): void
    {
        $valid = [true, false, 0, 1, '0', '1'];
        if (!in_array($value, $valid, true)) {
            throw new \Exception($this->messages['boolean']);
        }
    }

    /**
     * Formate le message d'erreur
     */
    protected function formatMessage(string $message, array $parameters): string
    {
        return strtr($message, array_map(function ($value) {
            return ':' . $value;
        }, array_flip($parameters)));
    }

    protected function validateExists(string $field, $value, string $parameter): void
    {
        [$table, $column] = array_pad(explode(',', $parameter), 2, null);
        $column = $column ?? 'id';

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
        $result = $this->db->fetchOne($sql, ['value' => $value]);

        if ($result['count'] === 0) {
            throw new \Exception($this->formatMessage($this->messages['exists'], [
                'attribute' => $field
            ]));
        }
    }

    protected function validateSometimes(string $field, $value): void
    {
        // Cette règle indique simplement que le champ est optionnel
        return;
    }
}

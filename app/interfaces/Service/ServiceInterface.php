<?php

namespace App\Interfaces\Service;

use Core\Exception\ServiceException;

/**
 * Interface générique pour les services
 * 
 * @template T
 */
interface ServiceInterface
{
    /**
     * Récupère une entité par son ID
     *
     * @param int $id
     * @return T|null
     * @throws ServiceException
     */
    public function get(int $id): ?object;

    /**
     * Récupère toutes les entités
     *
     * @return T[]
     * @throws ServiceException
     */
    public function getAll(): array;

    /**
     * Crée une nouvelle entité
     *
     * @param array $data
     * @return T
     * @throws ServiceException
     */
    public function create(array $data): object;

    /**
     * Met à jour une entité
     *
     * @param int $id
     * @param array $data
     * @return T
     * @throws ServiceException
     */
    public function update(int $id, array $data): object;

    /**
     * Supprime une entité
     *
     * @param int $id
     * @return bool
     * @throws ServiceException
     */
    public function delete(int $id): bool;

    /**
     * Valide les données
     *
     * @param array $data
     * @param array $rules Règles de validation optionnelles
     * @return bool
     * @throws ServiceException Si la validation échoue
     */
    public function validate(array $data, array $rules = []): bool;

    /**
     * Vérifie si une entité existe
     *
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Obtient les erreurs de validation
     *
     * @return array
     */
    public function getErrors(): array;
}
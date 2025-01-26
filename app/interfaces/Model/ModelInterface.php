<?php

namespace App\Interfaces\Model;

/**
 * Interface pour les modèles de l'application
 */
interface ModelInterface
{
    /**
     * Constructeur
     */
    public function __construct(array $attributes = []);

    /**
     * Trouve tous les enregistrements
     */
    public static function findAll(): array;

    /**
     * Trouve un enregistrement par son ID
     */
    public static function findById(int $id): ?self;

    /**
     * Trouve des enregistrements selon une colonne et une valeur
     */
    public static function findBy(string $column, $value): array;

    /**
     * Trouve des enregistrements selon plusieurs critères
     */
    public static function findByCriteria(array $criteria): array;

    /**
     * Sauvegarde le modèle
     */
    public function save(): bool;

    /**
     * Supprime un enregistrement par son ID
     */
    public static function delete(int $id): bool;

    /**
     * Supprime l'instance courante
     */
    public function deleteInstance(): bool;

    /**
     * Convertit le modèle en tableau
     */
    public function toArray(): array;

    /**
     * Définit un attribut
     */
    public function __set(string $name, $value): void;

    /**
     * Récupère un attribut
     */
    public function __get(string $name);

    /**
     * Obtient l'ID de l'enregistrement
     */
    public function getId(): ?int;

    /**
     * Obtient les attributs du modèle
     */
    public function getAttributes(): array;

    /**
     * Obtient les champs remplissables du modèle
     */
    public static function getFillable(): array;

    /**
     * Obtient le nom de la table
     */
    public static function getTable(): string;
}
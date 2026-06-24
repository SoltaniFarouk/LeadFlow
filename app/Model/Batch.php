<?php
namespace App\Model;

class Batch {
    private $tel1;
    private $code_postal;
    private $ville;
    private $adresse;
    private $genre;
    private $nom;
    private $prenom;
    private $tel2;
    private $tel3;
    private $mobile;
    private $fax;
    private $habitat;
    private $age_moyen;
    private $ethnie;
    private $tel1_prospection;
    private $tel2_prospection;
    private $tel3_prospection;
    private $mobile_prospection;
    private $fax_prospection;

    public function __construct($row) {
        $this->tel1               = $row['tel1'] ?? null;
        $this->code_postal        = $row['code_postal'] ?? null;
        $this->ville              = $row['ville'] ?? null;
        $this->adresse            = $row['adresse'] ?? null;
        $this->genre              = $row['genre'] ?? null;
        $this->nom                = $row['nom'] ?? null;
        $this->prenom             = $row['prenom'] ?? null;
        $this->tel2               = $row['tel2'] ?? null;
        $this->tel3               = $row['tel3'] ?? null;
        $this->mobile             = $row['mobile'] ?? null;
        $this->fax                = $row['fax'] ?? null;
        $this->habitat            = $row['habitat'] ?? null;
        $this->age_moyen          = $row['age_moyen'] ?? null;
        $this->ethnie             = $row['ethnie'] ?? null;
        $this->tel1_prospection   = $row['tel1_prospection'] ?? null;
        $this->tel2_prospection   = $row['tel2_prospection'] ?? null;
        $this->tel3_prospection   = $row['tel3_prospection'] ?? null;
        $this->mobile_prospection = $row['mobile_prospection'] ?? null;
        $this->fax_prospection    = $row['fax_prospection'] ?? null;
    }

    // --- Getters ---
    public function getTel1()               { return $this->tel1; }
    public function getCodePostal()         { return $this->code_postal; }
    public function getVille()              { return $this->ville; }
    public function getAdresse()            { return $this->adresse; }
    public function getGenre()              { return $this->genre; }
    public function getNom()                { return $this->nom; }
    public function getPrenom()             { return $this->prenom; }
    public function getTel2()               { return $this->tel2; }
    public function getTel3()               { return $this->tel3; }
    public function getMobile()             { return $this->mobile; }
    public function getFax()                { return $this->fax; }
    public function getHabitat()            { return $this->habitat; }
    public function getAgeMoyen()           { return $this->age_moyen; }
    public function getEthnie()             { return $this->ethnie; }
    public function getTel1Prospection()    { return $this->tel1_prospection; }
    public function getTel2Prospection()    { return $this->tel2_prospection; }
    public function getTel3Prospection()    { return $this->tel3_prospection; }
    public function getMobileProspection()  { return $this->mobile_prospection; }
    public function getFaxProspection()     { return $this->fax_prospection; }
}

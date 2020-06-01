<?php
    
    namespace App\Entity;
    
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    
    use Doctrine\ORM\Mapping as ORM;
    
    
    class SearchSpecies
    {
        
        //Global
        /**
         * @var string | null
         */
        private $speciesName;
        
        /**
         * @var string| null
         */
        private $ecofunctional;
        
        /**
         * @var string| null
         */
        private $origin;
        
        /**
         * @var string| null
         */
        private $Medinvasive;
        
        //Med
        
        /**
         * @var int | null
         */
        private $med1stSighting;
        
        
        /**
         * @var string| null
         */
        private $MedsuccessType;
        
        /**
         * @var string| null
         */
        private $Medstatus;
        
        /**
         * @var string| null
         */
        private $MedvectorName;
        
        //Country
        /**
         * @var string| null
         */
        private $country;
        
        /**
         * @var int | null
         */
        private $country1stSighting;
        
        /**
         * @var string| null
         */
        private $regionalSea;
        
        /**
         * @var string| null
         */
        private $Ecap;
        
        /**
         * @var string| null
         */
        private $countrystatus;
        
        /**
         * @var string| null
         */
        private $countryvectorName;
        
        
    }

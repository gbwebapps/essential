<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione dei parametri per il caricamento, il ridimensionamento e la gestione dei file multimediali.
 */
class Upload extends BaseConfig
{
	/**
	 * Specifica se abilitare la ridenominazione automatica delle immagini caricate
	 * 
	 * @var int 
	 */
	public int $renameImages = 0;

	/**
	 * Specifica se consentire la sovrascrizione dei file esistenti con lo stesso nome
	 * 
	 * @var int 
	 */
	public int $overwriteImages = 0;

	/**
	 * Dimensione orizzontale in pixel per il ridimensionamento medio delle immagini
	 * 
	 * @var int 
	 */
	public int $resizeMediumX = 960;

	/**
	 * Dimensione verticale in pixel per il ridimensionamento medio delle immagini
	 * 
	 * @var int 
	 */
	public int $resizeMediumY = 540;

	/**
	 * Dimensione orizzontale in pixel per la versione ridotta (miniatura) delle immagini
	 * 
	 * @var int 
	 */
	public int $resizeSmallX = 96;

	/**
	 * Dimensione verticale in pixel per la versione ridotta (miniatura) delle immagini
	 * 
	 * @var int 
	 */
	public int $resizeSmallY = 54;

	/**
	 * Dimensione massima consentita per il file caricato espressa in kilobyte
	 * 
	 * @var int 
	 */
	public int $maxFileSize = 4096;

	/**
	 * Larghezza massima consentita in pixel per le immagini caricate
	 * 
	 * @var int 
	 */
	public int $maxImageX = 1920;

	/**
	 * Altezza massima consentita in pixel per le immagini caricate
	 * 
	 * @var int 
	 */
	public int $maxImageY = 1080;

	/**
	 * Elenco delle estensioni di file consentite per l'upload
	 * 
	 * @var string 
	 */
	public string $allowedExtensions = 'png|jpg|jpeg|webp';
}
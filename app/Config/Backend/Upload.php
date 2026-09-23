<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione dei parametri per il caricamento, il ridimensionamento e la gestione dei file multimediali.
 */
class Upload extends BaseConfig
{
	/**
	 * @var int Specifica se abilitare la ridenominazione automatica delle immagini caricate
	 */
	public int $renameImages = 0;

	/**
	 * @var int Specifica se consentire la sovrascrizione dei file esistenti con lo stesso nome
	 */
	public int $overwriteImages = 0;

	/**
	 * @var int Dimensione orizzontale in pixel per il ridimensionamento medio delle immagini
	 */
	public int $resizeMediumX = 960;

	/**
	 * @var int Dimensione verticale in pixel per il ridimensionamento medio delle immagini
	 */
	public int $resizeMediumY = 540;

	/**
	 * @var int Dimensione orizzontale in pixel per la versione ridotta (miniatura) delle immagini
	 */
	public int $resizeSmallX = 96;

	/**
	 * @var int Dimensione verticale in pixel per la versione ridotta (miniatura) delle immagini
	 */
	public int $resizeSmallY = 54;

	/**
	 * @var int Dimensione massima consentita per il file caricato espressa in kilobyte
	 */
	public int $maxFileSize = 4096;

	/**
	 * @var int Larghezza massima consentita in pixel per le immagini caricate
	 */
	public int $maxImageX = 1920;

	/**
	 * @var int Altezza massima consentita in pixel per le immagini caricate
	 */
	public int $maxImageY = 1080;

	/**
	 * @var string Elenco delle estensioni di file consentite per l'upload
	 */
	public string $allowedExtensions = 'png|jpg|jpeg|webp';
}
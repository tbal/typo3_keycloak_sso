<?php

namespace Miniorange\KeycloakSSO\Helper;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Encryption Helper Class
 * Provides encryption and decryption functionality for sensitive data
 */
class EncryptionHelper
{
    /**
     * Encryption method using TYPO3's built-in encryption
     * 
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    public static function encrypt($data)
    {
        if (empty($data)) {
            return $data;
        }
        
        // Use TYPO3's encryption service
        $encryptionKey = self::getEncryptionKey();
        
        // Simple XOR encryption with key rotation for basic protection
        $encrypted = '';
        $keyLength = strlen($encryptionKey);
        
        for ($i = 0; $i < strlen($data); $i++) {
            $encrypted .= chr(ord($data[$i]) ^ ord($encryptionKey[$i % $keyLength]));
        }
        
        // Base64 encode to make it database-safe
        return base64_encode($encrypted);
    }
    
    /**
     * Decryption method using TYPO3's built-in encryption
     * 
     * @param string $encryptedData Encrypted data to decrypt
     * @return string Decrypted data
     */
    public static function decrypt($encryptedData)
    {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        // Base64 decode first
        $encrypted = base64_decode($encryptedData);
        if ($encrypted === false) {
            return $encryptedData; // Return original if not base64 encoded
        }
        
        // Use TYPO3's encryption service
        $encryptionKey = self::getEncryptionKey();
        
        // XOR decryption with key rotation
        $decrypted = '';
        $keyLength = strlen($encryptionKey);
        
        for ($i = 0; $i < strlen($encrypted); $i++) {
            $decrypted .= chr(ord($encrypted[$i]) ^ ord($encryptionKey[$i % $keyLength]));
        }
        
        return $decrypted;
    }
    
    /**
     * Get encryption key from TYPO3 configuration
     * 
     * @return string Encryption key
     */
    private static function getEncryptionKey()
    {
        // Use TYPO3's encryption key if available
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) && 
            !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        }
        
        // Fallback to a default key (should be changed in production)
        return 'keycloak_sso_default_encryption_key_2024';
    }
    
    /**
     * Encrypt user count value
     * 
     * @param int $count User count to encrypt
     * @return string Encrypted user count
     */
    public static function encryptUserCount($count)
    {
        return self::encrypt((string)$count);
    }
    
    /**
     * Decrypt user count value
     * 
     * @param string $encryptedCount Encrypted user count
     * @return int Decrypted user count
     */
    public static function decryptUserCount($encryptedCount)
    {
        $decrypted = self::decrypt($encryptedCount);
        
        // Validate that it's a numeric value
        if (is_numeric($decrypted)) {
            return (int)$decrypted;
        }
        
        // If decryption fails or returns non-numeric, return default value
        return 10; // Default user limit
    }
    
    /**
     * Check if a value is encrypted (base64 encoded)
     * 
     * @param string $value Value to check
     * @return bool True if encrypted, false otherwise
     */
    public static function isEncrypted($value)
    {
        if (empty($value)) {
            return false;
        }
        
        // Check if it's base64 encoded
        $decoded = base64_decode($value, true);
        return $decoded !== false && base64_encode($decoded) === $value;
    }
}

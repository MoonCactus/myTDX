<?php
/*
	2FA TOTP (Time-based One-Time Password) implementation for myTDX
	RFC 6238 compliant
	Licensed under the GNU GPL v2 license.
*/

class TOTP
{
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random secret key
     * @param int $length Length of the secret (default 16 bytes = 32 base32 chars)
     * @return string Base32 encoded secret
     */
    public static function generateSecret($length = 16)
    {
        $secret = random_bytes($length);
        return self::base32Encode($secret);
    }

    /**
     * Verify a TOTP code against a secret
     * @param string $secret Base32 encoded secret
     * @param string $code User-provided code (6-8 digits)
     * @param int $window Number of steps to check before/after current (default 1)
     * @param int $step Time step in seconds (default 30)
     * @return bool True if code is valid
     */
    public static function verify($secret, $code, $window = 1, $step = 30)
    {
        $code = trim($code);
        // Remove any whitespace or formatting
        $code = preg_replace('/\s+/', '', $code);
        
        if (!preg_match('/^[0-9]{6,8}$/', $code)) {
            return false;
        }

        $secret = self::base32Decode($secret);
        $timestamp = time();

        // Check current and adjacent time steps
        for ($i = -$window; $i <= $window; $i++) {
            $testTimestamp = floor($timestamp / $step) + $i;
            $calculated = self::calculateCode($secret, $testTimestamp);
            
            // Pad code to same length for comparison
            $paddedCode = str_pad($code, 6, '0', STR_PAD_LEFT);
            $paddedCalculated = str_pad($calculated, 6, '0', STR_PAD_LEFT);
            
            if ($paddedCode === $paddedCalculated) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current TOTP code for a secret (for testing/display)
     * @param string $secret Base32 encoded secret
     * @return string Current 6-digit code
     */
    public static function getCurrentCode($secret)
    {
        $secret = self::base32Decode($secret);
        $timestamp = floor(time() / 30);
        return str_pad(self::calculateCode($secret, $timestamp), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get TOTP URI for QR code generation
     * @param string $issuer Application name (e.g., "myTDX")
     * @param string $username User identifier
     * @param string $secret Base32 encoded secret
     * @return string otpauth:// URI
     */
    public static function getProvisioningUri($issuer, $username, $secret)
    {
        $issuer = urlencode($issuer);
        $username = urlencode($username);
        $secret = str_replace('=', '', $secret); // Remove padding for URI
        return "otpauth://totp/{$issuer}:{$username}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Calculate TOTP code from secret and timestamp
     * @param string $secret Binary secret key
     * @param int $timestamp Unix timestamp divided by step (e.g., time()/30)
     * @return int Calculated code
     */
    private static function calculateCode($secret, $timestamp)
{
    // Convert timestamp to 8-byte BIG-ENDIAN (network byte order)
    // RFC 6238: T is a 64-bit value, must be in big-endian
    $timeBytes = pack('N', $timestamp >> 32) . pack('N', $timestamp & 0xFFFFFFFF);

    // Calculate HMAC-SHA1
    $hash = hash_hmac('sha1', $timeBytes, $secret, true);

    // Dynamic truncation (RFC 6238 section 5.3)
    $offset = ord($hash[19]) & 0x0F;
    $truncated = substr($hash, $offset, 4);

    // Convert to 32-bit integer (big-endian)
    $code = unpack('N', $truncated)[1];

    // Mask to 31 bits (discard MSB per RFC)
    $code = $code & 0x7FFFFFFF;

    // Modulo to get 6-digit code
    return $code % 1000000;
}

    /**
     * Base32 encode
     * @param string $data Binary data
     * @return string Base32 encoded string
     */
    private static function base32Encode($data)
    {
        $result = '';
        $buffer = 0;
        $bits = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bits += 8;
            
            while ($bits >= 5) {
                $bits -= 5;
                $index = ($buffer >> $bits) & 0x1F;
                $result .= self::$base32Chars[$index];
            }
        }
        
        if ($bits > 0) {
            $buffer = $buffer << (5 - $bits);
            $index = $buffer & 0x1F;
            $result .= self::$base32Chars[$index];
        }
        
        // Add padding
        while (strlen($result) % 8 !== 0) {
            $result .= '=';
        }
        
        return $result;
    }

    /**
     * Base32 decode
     * @param string $data Base32 encoded string
     * @return string Binary data
     */
    private static function base32Decode($data)
    {
        $result = '';
        $buffer = 0;
        $bits = 0;
        $data = strtoupper(rtrim($data, '='));
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            $index = strpos(self::$base32Chars, $char);
            
            if ($index === false) {
                // Skip invalid characters
                continue;
            }
            
            $buffer = ($buffer << 5) | $index;
            $bits += 5;
            
            if ($bits >= 8) {
                $bits -= 8;
                $result .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        
        return $result;
    }
}

?>

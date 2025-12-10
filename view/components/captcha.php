<?php
/**
 * ============================================================================
 * CAPTCHA.PHP - Dynamic CAPTCHA Image Generator
 * ============================================================================
 * 
 * Generates random 5-character CAPTCHA image untuk prevent automated submissions.
 * Serves sebagai both view component AND dynamic endpoint.
 * 
 * DUAL PURPOSE:
 * 1. DYNAMIC ENDPOINT: Accessed via <img src="captcha.php">
 * 2. SESSION STORAGE: Stores code in $_SESSION['code'] untuk server-side validation
 * 
 * FEATURES:
 * 1. RANDOM CODE GENERATION
 *    - Function: acakCaptcha()
 *    - Length: 5 characters
 *    - Character set: a-z, A-Z (52 chars total)
 *    - Random selection: rand(0, 50) untuk each character
 *    - Returns: String (imploded array)
 * 
 * 2. IMAGE GENERATION (GD Library)
 *    - Dimensions: 173x50 pixels
 *    - Background: Blue (#165691 - RGB 22, 86, 165)
 *    - Text color: Light gray (#DFE6E9 - RGB 223, 230, 233)
 *    - Font: Built-in GD font #12
 *    - Position: 50px from left, 15px from top
 *    - Format: JPEG output
 * 
 * 3. SESSION MANAGEMENT
 *    - Starts session if not already started
 *    - Stores code in: $_SESSION['code']
 *    - Used for validation: Case-insensitive comparison
 *    - Regenerated on each request
 * 
 * 4. HTTP HEADERS
 *    - Content-Type: image/jpg
 *    - No cache headers (implicit - regenerates each time)
 *    - Direct image output (no HTML wrapper)
 * 
 * CODE GENERATION ALGORITHM:
 * 1. Define alphabet: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
 * 2. Calculate max index: strlen(alphabet) - 2 = 50
 * 3. Loop 5 times:
 *    - Generate random index: rand(0, 50)
 *    - Select character from alphabet
 *    - Append to array
 * 4. Implode array to string
 * 5. Return code
 * 
 * IMAGE GENERATION STEPS:
 * 1. imagecreatetruecolor(173, 50): Create blank image canvas
 * 2. imagecolorallocate(): Allocate colors (background + text)
 * 3. imagefill(): Fill canvas dengan background color
 * 4. imagestring(): Draw text on canvas
 *    - Parameters: image, font, x, y, text, color
 *    - Font #12: Medium-sized built-in font
 *    - Position: (50, 15) centers text roughly
 * 5. header('content-type: image/jpg'): Set MIME type
 * 6. imagejpeg($wh): Output image as JPEG
 * 7. imagedestroy($wh): Free memory
 * 
 * USAGE IN VIEWS:
 * ```php
 * <img src="<?= $asset('view/components/captcha.php') ?>" 
 *      id="captchaImage" 
 *      alt="CAPTCHA" 
 *      class="w-[173px] h-[50px]" />
 * ```
 * 
 * REFRESH FUNCTIONALITY:
 * - JavaScript: assets/js/captcha.js
 * - Pattern: captchaImage.src = captchaUrl + '?r=' + Date.now();
 * - Cache-busting: Query parameter forces reload
 * - Each request generates new code AND image
 * 
 * VALIDATION PATTERN (Controller):
 * ```php
 * $userInput = $_POST['captcha'];
 * $sessionCode = $_SESSION['code'] ?? '';
 * if (strtolower($userInput) !== strtolower($sessionCode)) {
 *     // Invalid CAPTCHA
 * }
 * ```
 * 
 * SESSION LIFECYCLE:
 * 1. User loads page dengan CAPTCHA image
 * 2. captcha.php generates code + stores in session
 * 3. User sees image, types code
 * 4. Form submission sends user input
 * 5. Controller compares input dengan $_SESSION['code']
 * 6. Match: Proceed; Mismatch: Error
 * 
 * SECURITY CONSIDERATIONS:
 * - Case-insensitive: Easier for users, still prevents bots
 * - Random generation: Can't predict next code
 * - Session-based: Each user gets unique code
 * - Image output: Harder for OCR to read (simple protection)
 * - No reuse: New code generated on each image request
 * 
 * BROWSER BEHAVIOR:
 * - <img> tag sends GET request to captcha.php
 * - PHP executes, generates image, outputs JPEG stream
 * - Browser renders image inline
 * - Refresh button/click triggers new GET request
 * 
 * GD LIBRARY REQUIREMENTS:
 * - PHP extension: gd
 * - Check: extension=gd in php.ini
 * - Functions used:
 *   * imagecreatetruecolor()
 *   * imagecolorallocate()
 *   * imagefill()
 *   * imagestring()
 *   * imagejpeg()
 *   * imagedestroy()
 * 
 * TROUBLESHOOTING:
 * - Black image: GD extension not loaded
 * - No image: Check file permissions
 * - Session issues: Ensure session_start() called
 * - Validation fails: Check case-insensitive comparison
 * 
 * INTEGRATION:
 * - Used in: login.php, register.php
 * - Refresh script: assets/js/captcha.js
 * - Validation: LoginController::auth(), RegisterController::submit()
 * 
 * IMPROVEMENT SUGGESTIONS:
 * - Add distortion for better security
 * - Use TTF fonts untuk better appearance
 * - Add noise/lines to prevent OCR
 * - Increase character count untuk stronger protection
 * - Add audio CAPTCHA alternative (accessibility)
 * 
 * @package BookEZ
 * @subpackage Views\Components
 * @version 1.0
 */
session_start();
    function acakCaptcha()
    {
        $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    //untuk menyatakan $pass sebagai array
    $pass = array();

    //masukkan -2 dalam string length
    $panjangAlpha = strlen($alphabet) - 2;
    for ($i = 0; $i < 5; $i++) {
        $n = rand(0, $panjangAlpha);
        $pass[] = $alphabet[$n];
    }

    //rubah array menjadi string
    return implode($pass);
}

//untuk mengacak captcha
$code = acakCaptcha();
$_SESSION["code"] = $code;

//lebar dan tinggi captcha
$wh = imagecreatetruecolor(173, 50);

//background color biru
$bgc = imagecolorallocate($wh, 22, 86, 165);

//text color abu-abu
$fc = imagecolorallocate($wh, 223, 230, 233);

imagefill($wh, 0, 0, $bgc);

//( $image , $fontsize , $string , $fontcolor )
imagestring($wh, 12, 50, 15, $code, $fc);

header('content-type: image/jpg');
imagejpeg($wh);
imagedestroy($wh);

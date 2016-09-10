<?php
/**
 * @copyright
 *
 * LICENSE MIT
 *
 * Copyright (c) 2016 Lauro Moraes [https://github.com/subversivo58]
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace HandlerSessionManager;
class HandlerSessionManager
{
      private $dbCollection;
      private $dbSession;
      private $dbName;
      private $crypto;
      private $expire;
      private $token;
      private $clean;
      private $key;

      /**
       * Constructor (require openssl and mbstring)
       *
       * @param array $options
       * @throws Fatal Error
       */
      public function __construct($options = false)
      {
          // define name of colection (set session_name() or default session_name())
          $this->dbCollection = $options['collection'] ?? session_name();

          // define database name
          $this->dbName       = ( $options[ 'databasename' ] ?? 'SessionManager' );
          
          // define database for connection in MongoDB (if no have.. create.)
          $this->dbSession    = (new MongoDB\Client)->{$this->dbName};
          
          // define expiration time session (get default or choose (options or default 1 hour))
          $this->expire       = get_cfg_var( "session.gc_maxlifetime" ) ?? ( (isset($options['expire']) AND is_numeric($options['expire']) ) ?? 3600 );
          
          // define token (for request session values in another fonts e.g: in nodejs)
          $this->token        = ( isset($options[ 'token' ]) AND is_string($options[ 'token' ]) AND $options['token'] != '' ) ?? false;
          
          // define if use auto_garbage function for clean old sessions (default false)
          $this->clean        = (  isset($options['force_garbage']) AND is_bool($options['force_garbage']) AND $options['force_garbage'] == true ) ?? false;

          // define if use crypto functions to encrypt|decrypt session data content
          $this->crypto       = (  isset($options['encrypt']) AND is_bool($options['encrypt']) AND $options['encrypt'] == true ) ?? false;
          
          // check requirements (if crypto is enabled)
          if ($this->crypto){
              // prevent check required openssl (for crypto|decrypto)
              if (! extension_loaded('openssl')) {
                  throw new \RuntimeException(sprintf(
                      "You need the OpenSSL extension to use %s",
                      __CLASS__
                  ));
              }
              // prevent check mbstring (for multibytes)
              if (! extension_loaded('mbstring')) {
                  throw new \RuntimeException(sprintf(
                      "You need the Multibytes extension to use %s",
                      __CLASS__
                  ));
              }
          }

          // define name of session
          session_name($this->dbCollection);
          
          // set save handler
          session_set_save_handler(
               [ $this, 'open'    ],
               [ $this, 'close'   ],
               [ $this, 'read'    ],
               [ $this, 'write'   ],
               [ $this, 'destroy' ],
               [ $this, 'gc'      ]
          );
          
          // shutdown
          register_shutdown_function('session_write_close');
          
          // check if session already started
          if(!isset($_SESSION)){
             session_start();
          }
          
          // auto call
          self::auto_garbage();
      }
      
      /**
       * Open
       *
       * @return boolean (true|false)
       * @throws false
       */
      public function open()
      {
          try{
              $this->key = $this->getKey($this->dbCollection);
              return $this->dbSession ? true : false;
          }catch(Exception $e){
              return false;
          }
      }

      /**
       * Close
       *
       * @return boolean true
       */
      public function close()
      {
          $this->dbSession = null;
          return true;
      }
      
      /**
       * Read session data (from MongoDB)
       *
       * @return string
       * @throws string ("")
       */
      public function read($id)
      {
          try{
              $doc = $this->dbSession->{$this->dbCollection}->findOne( ["_id" => $id] );
              // chek if is object... check if crypto is enabled. retur data or empty string
              return is_object($doc) ? ( $this->crypto ? $this->decrypt( utf8_decode($doc->sessionData), $this->key ) : $doc->sessionData ) : "";
          }catch(Exception $e){
              return "";
          }   
      }
      
      /**
       * Write session data
       *
       * @return boolean true
       * @throws null
       */
      public function write($id, $data)
      {
          // check if crypto is enabled
          $encdata = $this->crypto ? utf8_encode( $this->encrypt($data, $this->key) ) : $data;
          try{
              $create = $this->dbSession->{$this->dbCollection}->updateOne( ["_id" => $id], ['$set' => ["_id" => $id, "token" => $this->token, "sessionData" => $encdata, "expire" => ( time() + $this->expire )] ], ["upsert" => true] );
          }catch(Exception $e){}
          return true;
      }
      
      /**
       * Destruct session (unset|session_destroy)
       *
       * @return boolean (true|false)
       * @throws boolean false
       */
      public function destroy($id)
      {
          try{
              $result = $this->dbSession->{$this->dbCollection}->findOneAndDelete( ["_id" => $id] );
              return $result ? true : false;
          }catch(Exception $e){
              return false;
          }
      }
      
      /**
       * Default garbage collector (respect INI parameter)
       *
       * @return boolean (true)
       * @throws null
       */
      public function gc()
      {
          try{
              $result = $this->dbSession->{$this->dbCollection}->findOneAndDelete( ["expire" => ['$lt' => time()]] );
          }catch(Exception $e){}
          return true;
      }

      /**
       * Auto Garbage (force clean) remove obsolet sessions
       *
       */
      private function auto_garbage()
      {
          if($this->clean){
             $this->dbSession->{$this->dbCollection}->findOneAndDelete( ["expire" => ['$lt' => time()]] );
          }
      }

      /**
       * The functions encrypt|decrypt|getKey|hash_equals is part of code Enrico Zimuel
       * @author https://github.com/ezimuel
       * @see https://github.com/ezimuel/PHP-Secure-Session
       *
       * Many thanks!
       */
       

      /**
       * Encrypt and authenticate
       *
       * @param string $data
       * @param string $key
       * @return string
       */
      private function encrypt($data, $key)
      {
          $iv = random_bytes(16); // AES block size in CBC mode
          // Encryption
          $ciphertext = openssl_encrypt(
              $data,
              'AES-256-CBC',
              mb_substr($key, 0, 32, '8bit'),
              OPENSSL_RAW_DATA,
              $iv
          );
          // Authentication
          $hmac = hash_hmac(
              'SHA256',
              $iv . $ciphertext,
              mb_substr($key, 32, null, '8bit'),
              true
          );
          return $hmac . $iv . $ciphertext;
      }  

      /**
       * Authenticate and decrypt
       *
       * @param string $data
       * @param string $key
       * @return string
       * @throws Fatal error
       */
      private function decrypt($data, $key)
      {
          $hmac       = mb_substr($data, 0, 32, '8bit');
          $iv         = mb_substr($data, 32, 16, '8bit');
          $ciphertext = mb_substr($data, 48, null, '8bit');
          // Authentication
          $hmacNew = hash_hmac(
              'SHA256',
              $iv . $ciphertext,
              mb_substr($key, 32, null, '8bit'),
              true
          );
          if (! $this->hash_equals($hmac, $hmacNew)) {
              throw new \RuntimeException('Authentication failed');
          }
          // Decrypt
          return openssl_decrypt(
              $ciphertext,
              'AES-256-CBC',
              mb_substr($key, 0, 32, '8bit'),
              OPENSSL_RAW_DATA,
              $iv
          );
      }  

      /**
       * Get the encryption and authentication keys from cookie
       *
       * @param string $name
       * @return string
       */
      private function getKey($name)
      {
          if (empty($_COOKIE[$name])) {
              $key = random_bytes(64); // 32 for encryption and 32 for authentication
              $cookieParam = session_get_cookie_params();
              setcookie(
                  $name,
                  base64_encode($key),
                  // if session cookie lifetime > 0 then add to current time
                  // otherwise leave it as zero, honoring zero's special meaning
                  // expire at browser close.
                  ($cookieParam['lifetime'] > 0) ? time() + $cookieParam['lifetime'] : 0,
                  $cookieParam['path'],
                  $cookieParam['domain'],
                  $cookieParam['secure'],
                  $cookieParam['httponly']
              );
          } else {
              $key = base64_decode($_COOKIE[$name]);
          }
          return $key;
      }  

      /**
       * Hash equals function for PHP 5.5+
       *
       * @param string $expected
       * @param string $actual
       * @return bool
       */
      private function hash_equals($expected, $actual)
      {
          $expected     = (string) $expected;
          $actual       = (string) $actual;
          if (function_exists('hash_equals')) {
              return hash_equals($expected, $actual);
          }
          $lenExpected  = mb_strlen($expected, '8bit');
          $lenActual    = mb_strlen($actual, '8bit');
          $len          = min($lenExpected, $lenActual);
          $result = 0;
          for ($i = 0; $i < $len; $i++) {
              $result |= ord($expected[$i]) ^ ord($actual[$i]);
          }
          $result |= $lenExpected ^ $lenActual;
          return ($result === 0);
      }
      
      /**
       * Destruct
       */
      public function __destruct()
      {
          session_write_close();
      }
}

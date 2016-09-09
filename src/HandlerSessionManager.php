<?php
/**
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
      private $expire;
      private $token;
      private $clean;

      public function __construct($options = false)
      {
          // define name of colection (set session_name() or default session_name())
          $this->dbCollection = $options['collection'] ?? session_name();

          // define database name
          $this->dbName       = ( $options[ 'databasename' ] ?? 'SessionManager' );
          
          // define database for connection in MongoDB (if no have.. create.)
          $this->dbSession    = (new MongoDB\Client)->{$this->dbName};
          
          // define expiration time session (get default or choose (options or default 1 hour))
          $this->expire       = get_cfg_var( "session.gc_maxlifetime" ) ?? ( $options['expire'] ?? 3600 );
          
          // define token (for request session values in another fonts e.g: in nodejs)
          $this->token        = $options[ 'token' ] ?? false;
          
          // define if use auto_garbage function for clean old sessions (default false)
          $this->clean        = $options[ 'force_garbage' ] ? true : false;

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

      public function open()
      {

          return $this->dbSession ? true : false;
      }

      public function close()
      {
          $this->dbSession = null;
          return true;
      }
 
      public function read($id)
      {
          $doc = $this->dbSession->{$this->dbCollection}->findOne( ["_id" => $id] );
          
          return is_object($doc) ? $doc->sessionData : "";
      }
 
      public function write($id, $data)
      {
          $create = $this->dbSession->{$this->dbCollection}->updateOne( ["_id" => $id], ['$set' => ["_id" => $id, "token" => $this->token, "sessionData" => $data, "expire" => ( time() + $this->expire )] ], ["upsert" => true] );
          
          return true;
      }
 
      public function destroy($id)
      {
          $result = $this->dbSession->{$this->dbCollection}->findOneAndDelete( ["_id" => $id] );

          return $result ? true : false;
      }
 
      public function gc()
      {
          $result = $this->dbSession->{$this->dbCollection}->findOneAndDelete( ["expire" => ['$lt' => time()]] );

          return true;
      }

      public function auto_garbage()
      {
          if($this->clean){
             $this->dbSession->{$this->dbCollection}->findOneAndDelete( ["expire" => ['$lt' => time()]] );
          }
      }

      public function __destruct()
      {
          session_write_close();
      }
}

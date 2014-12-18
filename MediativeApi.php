<?php
/**
 * PHP SDK for Mediative API
 *
 * Let API developers use the Mediative API from external sources.
 *
 * @author Cominweb <contact@cominweb.com>
 * @copyright 2014 Cominweb
 */

require_once('Curl.php');
use \Curl\Curl;

/** 
* MediativeApi Class
* 
* PHP SDK for Mediative
* 
* @example new MediativeApi(PUBLIC_KEY, SECRET_KEY, DOMAIN_URL);
* @uses Curl\Curl to manage cUrl ressources
* @version 1.0
*/
class MediativeApi {
    /**
     * Define where the external API is reachable
     * @const API
     */
    const API = 'https://api.omi.tv/';
    /**
     * Define current version used
     * @const VERSION
     */
    const VERSION = 1;
    /**
     * Define the file extension wanted for results
     * @const EXT
     */
    const EXT = '.json';

    /**
     * Contains client secret token
     * @var string
     */
    protected $client_secret;

    /**
     * Contains client public token
     * @var string
     */
    protected $client_public;

    /**
     * Contains requested domain
     * @var string
     */
    protected $domain;

    /**
     * The Curl class used in the API
     * @var object
     */
    protected $curl;

    /**
     * The current session token used on the API to make requests
     * @var string
     */
    protected $token;

    /**
     * Set if SSL certificate has to be checked
     * @var bool
     */
    protected $secure;

    /**
     * Create an instance and set public and secret tokens, and domain name
     * @param string $public The public developer key
     * @param string $secret The secret developer key
     * @param string $domain The domain name to make requests, without protocol nor path (like demo.omi.tv)
     * @access public
     * @return object Return the current object
     */
    public function __construct($public, $secret, $domain) {
        $this->setPublic($public);
        $this->setSecret($secret);
        $this->setDomain($domain);

        $this->secure = true;
        return $this;
    }

    /**
     * Set the public developer key
     * @param string $public The public developer key to set
     * @access public
     * @return object Return the current instance for chainability
     */
    public function setPublic($public) {
        if(empty($public)) {
            throw new Exception('Please provide your public auth token.');
        }
        $this->client_public = $public;
        return $this;
    }

    /**
     * Get the public developer key
     * @access public
     * @return string Return the public key set
     */
    public function getPublic() {
        return $this->client_public;
    }

    /**
     * Set the secret developer key
     * @param string $secret The secret developer key to set
     * @access public
     * @return object Return the current instance for chainability
     */
    public function setSecret($secret) {
        if(empty($secret)) {
            throw new Exception('Please provide your secret auth token.');
        }
        $this->client_secret = $secret;
        return $this;
    }

    /**
     * Get the secret developer key
     * @access public
     * @return string Return the secret key set
     */
    public function getSecret() {
        return $this->client_secret;
    }

    /**
     * Set the domain in use
     * @param string $domain The domain to work on (without protocol nor path, eg. demo.omi.tv)
     * @access public
     * @return object Return the current instance for chainability
     */
    public function setDomain($domain) {
        if(empty($domain)) {
            throw new Exception('Please provide the domain on which you would work.');
        }
        if(!preg_match('#^([a-zA-Z0-9_-]+\.)*[a-zA-Z0-9\-_]+\.[a-zA-Z]{2,5}$#', $domain)) {
            throw new Exception('Please provide the domain without path and protocol.');
        }
        $this->domain = $domain;
        return $this;
    }


    /**
     * Get the domain on which we are working
     * @access public
     * @return string Return domain set
     */
    public function getDomain() {
        return $this->domain;
    }


    /**
     * Set the authorization token
     * @param string $token The token given by the API server
     * @access public
     * @return object Return the current instance for chainability
     */
    public function setToken($token) {
        if(empty($token)) {
            throw new Exception('Please provide the token given by the API.');
        }
        $this->token = $token;
        return $this;
    }


    /**
     * Get the current authorization token
     * @access public
     * @return string Return token set
     */
    public function getToken() {
        if(empty($this->token)) {
            throw new Exception('You should set your auth token before making a request.');
        }
        return $this->token;
    }


    /**
     * Disable SSL certificate checks, and allow self signed certificates (for demo purposes)
     * @access public
     * @return object Return the current instance for chainability
     */
    public function disableSecure() {
        $this->secure = false;
        return $this;
    }


    /**
     * Enable SSL certificate checks, and avoid self signed certificates (default)
     * @access public
     * @return object Return the current instance for chainability
     */
    public function enableSecure() {
        $this->secure = true;
        return $this;
    }


    /**
     * Get authorization token from the API server, and set the token if auth works
     * @access public
     * @throws Exception if the request cannot be done
     * @return object Return the current instance for chainability
     */
    public function auth() {
        $this->reset();
        $this->curl->setBasicAuthentication($this->getPublic(), $this->getSecret());
        $this->curl->setHeader('X-Requested-With', 'MediativeApi');
        $this->curl->setHeader('X-Requested-Version', $this::VERSION);
        if(!$this->secure) {
            $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        }
        $this->curl->get($this::API . '/api/login'.$this::EXT.'?'.http_build_query(array('domain' => $this->getDomain())));
        if ($this->curl->error) {
            throw new Exception($this->curl->error_message, $this->curl->error_code);
        } else {
            $response = $this->curl->response;
            if(isset($response->auth->token->token)) {
                $this->setToken($response->auth->token->token);
            } else {
                throw new Exception('Invalid developer login');
            }
        }
        return $this;
    }


    /**
     * Make a GET request on the API
     * @param string $ressource The ressource requested (eg. medias)
     * @param mixed $options The request options in array (limits, offsets, conditions, etc...), or integer to view the ressource ID provided
     * @param bool $autoMap Parse $options to be used as int wihch give ID to view (default: true)
     * @param bool $shortCut Do we return directly the requested ressource or the whole response datas (default: true)
     * @access public
     * @throws Exception if the request cannot be done, or if the response cannot be parsed
     * @return mixed Return the response content in an stdClass, or false
     */
    public function get($ressource, $options = array(), $autoMap = true, $shortCut = true) {
        $this->reset();
        $token = $this->getToken();
        if(is_numeric($options) && $autoMap === true) {
            $ressource .= '/'.$options;
            $options = array();
        } elseif(isset($options['id']) && $autoMap === true) {
            $ressource .= '/'.$options['id'];
            unset($options['id']);
        }
        $options = array_merge_recursive($options, array('token' => $this->getToken(), 'd' => $this->getDomain()));
        $this->curl->get('https://'.$this->getDomain().'/'.$ressource.$this::EXT, $options);
        if ($this->curl->error) {
            throw new Exception($this->curl->error_message, $this->curl->error_code);
            return false;
        } elseif(!isset($this->curl->response->response)) {
            throw new Exception('Cannot parse response datas');
            return $this->curl->response;
        } else {
            $response = $this->curl->response->response;
            $ressourceName = preg_replace('#^(\w{1,})(/\d{1,})?$#', '$1', $ressource);
            if(isset($response->{$ressourceName}) && $shortCut === true) {
                return $response->{$ressourceName};
            } else {
                return $response;
            }
        }
    }


    /**
     * Make a POST request on the API
     * @param string $ressource The ressource requested (eg. medias)
     * @param array $datas The request datas to POST
     * @param array $options The request options in array added to the URL (limits, offsets, conditions, etc...)
     * @access public
     * @throws Exception if the request cannot be done, or if the response cannot be parsed
     * @return object Return the response content in an stdClass
     */
    public function post($ressource, $datas = array(), $options = array()) {
        $this->reset();
        $token = $this->getToken();
        $options = array_merge_recursive($options, array('token' => $this->getToken(), 'd' => $this->getDomain()));
        $this->curl->post('https://'.$this->getDomain().'/'.$ressource.$this::EXT.'?'.http_build_query($options), $datas);
        if ($this->curl->error) {
            throw new Exception($this->curl->error_message, $this->curl->error_code);
            return $this->curl->response;
        } elseif(!isset($this->curl->response->response)) {
            throw new Exception('Cannot parse response datas');
            return $this->curl->response;
        } else {
            $response = $this->curl->response->response;
            $ressourceName = preg_replace('#^(\w{1,})(/\d{1,})?$#', '$1', $ressource);
            if(isset($response->{$ressourceName})) {
                return $response->{$ressourceName};
            } else {
                return $response;
            }
        }
    }

    
    /**
     * Make a PUT request on the API
     * @param string $ressource The ressource requested (eg. medias)
     * @param array $datas The datas to PUT
     * @param array $options The request options in array added to the URL (limits, offsets, conditions, etc...)
     * @param bool $check Check if an ID is provided to do the request (default: true)
     * @param bool $autoMap Update the ressource URL depending on the $datas param (default: true)
     * @access public
     * @throws Exception if the request cannot be done, or if the response cannot be parsed
     * @return object Return the response content in an stdClass
     */
    public function put($ressource, $datas= array(), $options = array(), $check = true, $autoMap = true) {
        $this->reset();
        $token = $this->getToken();
        if(!preg_match('#^\w{1,}/\d{1,}$#', $ressource) && !isset($datas['id']) && $check === true) {
            throw new Exception('Please provide an ID to update');
        }
        if(!preg_match('#^\w{1,}/\d{1,}$#', $ressource) && isset($datas['id']) && $autoMap === true) {
            $ressource .= '/'.$datas['id'];
        }
        $options = array_merge_recursive($options, array('token' => $this->getToken(), 'd' => $this->getDomain()));
        $this->curl->put('https://'.$this->getDomain().'/'.$ressource.$this::EXT.'?'.http_build_query($options), $datas);
        if($this->curl->error) {
            throw new Exception($this->curl->error_message, $this->curl->error_code);
            return $this->curl->response;
        } elseif(!isset($this->curl->response->response)) {
            throw new Exception('Cannot parse response datas');
            return $this->curl->response;
        } else {
            $response = $this->curl->response->response;
            $ressourceName = preg_replace('#^(\w{1,})(/\d{1,})?$#', '$1', $ressource);
            if(isset($response->{$ressourceName})) {
                return $response->{$ressourceName};
            } else {
                return $response;
            }
        }
    }

    
    /**
     * Make a DELETE request on the API
     * @param string $ressource The ressource requested (eg. medias)
     * @param mixed $options The request options in array added to the URL, or the (integer)ID to DELETE
     * @param bool $autoMap Update the ressource URL depending on the $options param (default: true)
     * @access public
     * @throws Exception if the request cannot be done, or if the response cannot be parsed
     * @return object Return the response content in an stdClass
     */
    public function delete($ressource, $options = array(), $autoMap = true) {
        $this->reset();
        $token = $this->getToken();
        if(is_numeric($options) && $autoMap === true) {
            $ressource .= '/'.$options;
            $options = array();
        } elseif(is_array($options) && isset($options['id']) && $autoMap === true) {
            $ressource .= '/'.$options['id'];
            unset($options['id']);
        }
        $options = array_merge_recursive($options, array('token' => $this->getToken(), 'd' => $this->getDomain()));
        $this->curl->delete('https://'.$this->getDomain().'/'.$ressource.$this::EXT, $options);
        if($this->curl->error) {
            throw new Exception($this->curl->error_message, $this->curl->error_code);
            return $this->curl->response;
        } elseif(!isset($this->curl->response->response)) {
            throw new Exception('Cannot parse response datas');
            return $this->curl->response;
        } else {
            $response = $this->curl->response->response;
            $ressourceName = preg_replace('#^(\w{1,})(/\d{1,})?$#', '$1', $ressource);
            if(isset($response->{$ressourceName})) {
                return $response->{$ressourceName};
            } else {
                return $response;
            }
        }
    }

    /**
     * Close the cUrl class, and its ressource
     * @access public
     * @return object Return the current instance for chainability
     */
    public function close() {
        $this->curl->close();
        return $this;
    }

    
    /**
     * Reset the cUrl connection by closing the current one, and create a new one
     * @access public
     * @return object Return the current instance for chainability
     */
    public function reset() {
        if($this->curl) {
            $this->close();
            $this->curl->init();
            if(!$this->secure) {
                $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            }
        } else {
            $this->curl = new Curl(); // new instance
        }
        return $this;
    }
}
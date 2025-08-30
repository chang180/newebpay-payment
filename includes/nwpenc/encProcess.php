<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class encProcess {


	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * MPG aes解密
	 *
	 * @access private
	 * @param array $parameter ,string $key, string $iv
	 * @version 1.4
	 * @return string
	 */
	public function create_aes_decrypt( $parameter = '', $key = '', $iv = '' ) {
		$dec_str = $this->strippadding( openssl_decrypt( hex2bin( $parameter ), 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv ) );
		if ( json_decode( $dec_str ) ) {
			$return_data = $this->decrypt_json_data( $dec_str );
		} else {
			$return_data = $this->decrypt_str_data( $dec_str );
		}

		return $return_data;
	}

	public function strippadding( $string ) {
		$slast  = ord( substr( $string, -1 ) );
		$slastc = chr( $slast );
		if ( preg_match( "/$slastc{" . $slast . '}/', $string ) ) {
			$string = substr( $string, 0, strlen( $string ) - $slast );
			return $string;
		} else {
			return false;
		}
	}

	public function decrypt_json_data( $dec_str ) {
		 $dec_data                     = json_decode( $dec_str, true );
		$dec_data['Result']['Status']  = $dec_data['Status'];
		$dec_data['Result']['Message'] = $dec_data['Message'];
		return $dec_data['Result']; // 整理成跟String回傳相同格式
	}

	public function decrypt_str_data( $dec_str ) {
		$dec_data = explode( '&', $dec_str );
		foreach ( $dec_data as $_ind => $value ) {
			$trans_data                    = explode( '=', $value );
			$return_data[ $trans_data[0] ] = $trans_data[1];
		}
		return $return_data;
	}

	/**
	 * MPG aes加密
	 *
	 * @access private
	 * @param array $parameter ,string $key, string $iv
	 * @version 1.4
	 * @return string
	 */
	public function create_mpg_aes_encrypt( $parameter, $key = '', $iv = '' ) {
		 $return_str = '';
		if ( ! empty( $parameter ) ) {
			ksort( $parameter );
			$return_str = http_build_query( $parameter );
		}
		return trim( bin2hex( openssl_encrypt( $this->addpadding( $return_str ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv ) ) );
	}

	private function addpadding( $string, $blocksize = 32 ) {
		$len     = strlen( $string );
		$pad     = $blocksize - ( $len % $blocksize );
		$string .= str_repeat( chr( $pad ), $pad );
		return $string;
	}

	/**
	 * MPG sha256加密
	 *
	 * @access private
	 * @param string $str ,string $key, string $iv
	 * @version 1.4
	 * @return string
	 */
	public function aes_sha256_str( $str, $key = '', $iv = '' ) {
		return strtoupper( hash( 'sha256', 'HashKey=' . $key . '&' . $str . '&HashIV=' . $iv ) );
	}
}

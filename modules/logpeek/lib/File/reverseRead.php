<?php
/**
 * Functionatility for line by line reverse reading of a file. It is done by blockwise
 * fetching the file from the end and putting the lines into an array.
 * 
 * @author Thomas Graff<thomas.graff@uninett.no>
 *
 */
class sspmod_logpeek_File_reverseRead{
	// 8192 is max number of octets limited by fread.
	private $blockSize;
	private $blockStart;
	private $fileHandle;
	// fileSize may be changed after initial file size check
	private $fileSize;
	private $fileMtime;
	// Array containing file lines
	private $content;
	// Leftover before first complete line
	private $remainder;
	// Count read lines from the end
	private $readPointer;
	
	/**
	 * File is checked and file handle to file is opend. But no data is read
	 * from the file.
	 * 
	 * @param string $fileUrl Path and filename to file to be read
	 * @param int $blockSize File read block size in byte
	 * @return bool Success
	 */
	public function __construct($fileUrl, $blockSize = 8192){
		if(!is_readable($fileUrl)){
			return FALSE;
		}
		
		$this->blockSize = $blockSize;
		$this->content = array();
		$this->remainder = '';
		$this->readPointer = 0;
		
		$fileInfo = stat($fileUrl);
		$this->fileSize = $this->blockStart = $fileInfo['size'];
		$this->fileMtime = $fileInfo['mtime'];
		
		if($this->fileSize > 0){
			$this->fileHandle = fopen($fileUrl, 'rb');
			return TRUE;
		}else{
			return FALSE;
		}
	}
	
	
	public function __destruct(){
		if(is_resource($this->fileHandle)){
			fclose($this->fileHandle);
		}
	}
	
	/**
	 * Fetch chunk of data from file.
	 * Each time this function is called, will it fetch a chunk
	 * of data from the file. It starts from the end of the file
	 * and work towards the beginning of the file.
	 * 
	 * @return string buffer with datablock.
	 * Will return bool FALSE when there is no more data to get.
	 */
	private function readChunk(){
		$splits = $this->blockSize;
		
		$this->blockStart -= $splits;
		if($this->blockStart < 0){
			$splits += $this->blockStart;
			$this->blockStart = 0;
		}
		
		// Return false if nothing more to read
		if($splits === 0){
			return FALSE;
		}
		
		fseek($this->fileHandle, $this->blockStart, SEEK_SET);
		$buff = fread($this->fileHandle, $splits);
		
		// $buff = stream_get_contents($this->fileHandle, $splits, $this->blockStart);
		
		return $buff;
	}
	
	/**
	 * Get one line of data from the file, starting from the end of the file.
	 * 
	 * @return string One line of data from the file.
	 * Bool FALSE when there is no more data to get.
	 */
	public function getPreviousLine(){
		if(count($this->content) === 0 || $this->readPointer < 1){
			
			do {
				$buff = $this->readChunk();
				
				if($buff !== FALSE){
					$eolPos = strpos($buff, "\n");
				}else{
					// Empty buffer, no more to read.
					if(strlen($this->remainder) > 0){
						$buff = $this->remainder;
						$this->remainder = '';
						// Exit from while-loop
						break;
					}else{
						// Remainder also empty.
						return FALSE;
					}
				}
				
				if($eolPos === FALSE){
					// No eol found. Make buffer head of remainder and empty buffer.
					$this->remainder = $buff . $this->remainder;
					$buff = '';
				}elseif($eolPos !== 0){
					// eol found.
					$buff .= $this->remainder;
					$this->remainder = substr($buff, 0, $eolPos);
					$buff = substr($buff, $eolPos+1);
				}elseif($eolPos === 0){
					$buff .= $this->remainder;
					$buff = substr($buff, 1);
					$this->remainder = '';
				}
				
			}while(($buff !== FALSE) && ($eolPos === FALSE));
			
			$this->content = explode("\n", $buff);
			$this->readPointer = count($this->content);
		}
		
		if(count($this->content) > 0){
			return $this->content[--$this->readPointer];
		}else{
			return FALSE;
		}
	}
	
	
	private function cutHead(&$haystack, $needle, $exit){
		$pos = 0;
		$cnt = 0;
		// Holder på inntill antall ønskede linjer eller vi ikke finner flere linjer
		while($cnt < $exit && ($pos = strpos($haystack, $needle, $pos)) !==false ){
			$pos++;
			$cnt++;
		}   
		return $pos == false? false: substr($haystack, $pos, strlen($haystack));
	}
	
	
	// FIXME: This function hawe som error, do not use before auditing and testing
	public function getTail($lines = 10){
		$this->blockStart = $this->fileSize;
		$buff1 = Array();
		$lastLines = array();
		
		while($this->blockStart){
			$buff = $this->readChunk();
			if(!$buff)break;
			
			$lines -= substr_count($buff, "\n");
			
			if($lines <= 0)
			{
				$buff1[] = $this->cutHead($buff, "\n", abs($lines)+1);
				break;
			}
			$buff1[] = $buff;
		}
		
		for($i = count($buff1); $i >= 0; $i--){
			$lastLines = array_merge($lastLines, explode("\n", $buff1[$i]));
		}
		
		return $lastLines;
		// return str_replace("\r", '', implode('', array_reverse($buff1)));
	}
	
	
	private function getLineAtPost($pos){
		if($pos < 0 || $pos > $this->fileSize){
			return FALSE;
		}
		
		$seeker = $pos;
		fseek($this->fileHandle, $seeker, SEEK_SET);
		while($seeker > 0 && fgetc($this->fileHandle) !== "\n"){
			fseek($this->fileHandle, --$seeker, SEEK_SET);
		}
		
		return rtrim(fgets($this->fileHandle));
	}
	
	
	public function getFirstLine(){
		return $this->getLineAtPost(0);
	}
	
	
	public function getLastLine(){
		return $this->getLineAtPost($this->fileSize-2);
	}
	
	
	public function getFileSize(){
		return $this->fileSize;
	}
	
	
	public function getFileMtime(){
		return $this->fileMtime;
	}
	
}
?>
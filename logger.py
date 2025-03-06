import logging
from datetime import datetime
import os

class Logger:
    def __init__(self, log_dir='logs'):
        # Create logs directory if it doesn't exist
        self.log_dir = log_dir
        os.makedirs(log_dir, exist_ok=True)
        
        # Set up logger
        self.logger = logging.getLogger('VideoDownloader')
        self.logger.setLevel(logging.INFO)
        
        # Create log file with current date
        log_file = os.path.join(
            log_dir, 
            f'video_downloader_{datetime.now().strftime("%Y%m%d_%H%M%S")}.log'
        )
        
        # Create file handler
        file_handler = logging.FileHandler(log_file, encoding='utf-8')
        file_handler.setLevel(logging.INFO)
        
        # Create console handler
        console_handler = logging.StreamHandler()
        console_handler.setLevel(logging.INFO)
        
        # Create formatter
        formatter = logging.Formatter(
            '%(asctime)s - %(levelname)s - %(message)s',
            datefmt='%Y-%m-%d %H:%M:%S'
        )
        
        # Add formatter to handlers
        file_handler.setFormatter(formatter)
        console_handler.setFormatter(formatter)
        
        # Add handlers to logger
        self.logger.addHandler(file_handler)
        self.logger.addHandler(console_handler)

    def log_error(self, error, url=None, context=None):
        """Log error with context information"""
        error_msg = f"Error: {str(error)}"
        if url:
            error_msg += f"\nURL: {url}"
        if context:
            error_msg += f"\nContext: {context}"
        self.logger.error(error_msg)

    def log_info(self, message):
        """Log general information"""
        self.logger.info(message)

    def log_download(self, url, filepath):
        """Log successful download"""
        self.logger.info(f"Successfully downloaded: {url} to {filepath}")

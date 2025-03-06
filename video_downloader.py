import yt_dlp
import tkinter as tk
import ttkbootstrap as ttk
from ttkbootstrap.constants import *
from tkinter import messagebox, filedialog
from PIL import Image, ImageTk
import os
import subprocess
import platform
import re
from logger import Logger

class DownloadHistoryWindow(tk.Toplevel):
    def __init__(self, parent, app):
        super().__init__(parent)
        self.app = app
        self.style = ttk.Style(theme='flatly')
        self.title('سجل التحميل - Download History')
        self.geometry('600x400')
        self.configure(padx=20, pady=20)
        
        # Add protocol handler for window close
        self.protocol("WM_DELETE_WINDOW", self.on_closing)

        # Create treeview
        columns = ('filename', 'path', 'date')
        self.tree = ttk.Treeview(self, columns=columns, show='headings')
        
        # Define headings
        self.tree.heading('filename', text='اسم الملف - Filename')
        self.tree.heading('path', text='المسار - Path')
        self.tree.heading('date', text='التاريخ - Date')
        
        # Configure columns
        self.tree.column('filename', width=200)
        self.tree.column('path', width=250)
        self.tree.column('date', width=150)
        
        # Add scrollbar
        scrollbar = ttk.Scrollbar(self, orient='vertical', command=self.tree.yview)
        self.tree.configure(yscrollcommand=scrollbar.set)
        
        # Pack elements
        self.tree.pack(side='left', fill='both', expand=True)
        scrollbar.pack(side='right', fill='y')
        
        # Bind double-click event
        self.tree.bind('<Double-1>', self.open_file)

    def add_download(self, filename, path, date):
        self.tree.insert('', 'end', values=(filename, path, date))

    def open_file(self, event):
        selected_item = self.tree.selection()[0]
        file_path = self.tree.item(selected_item)['values'][1]
        if platform.system() == 'Windows':
            os.startfile(file_path)
        elif platform.system() == 'Darwin':  # macOS
            subprocess.run(['open', file_path])
        else:  # Linux
            subprocess.run(['xdg-open', file_path])

    def on_closing(self):
        self.app.download_history = None
        self.destroy()

class SettingsWindow(ttk.Toplevel):  # تغيير من tk.Toplevel إلى ttk.Toplevel
    def __init__(self, parent, app):
        super().__init__(parent)
        self.app = app
        self.title('الإعدادات - Settings')
        self.geometry('600x700')
        
        # تطبيق التصميم الأساسي
        style = ttk.Style()
        self.configure(bg=style.colors.bg)
        
        # إنشاء الإطار الرئيسي
        main_frame = ttk.Frame(self, padding=15)
        main_frame.pack(fill=tk.BOTH, expand=True)
        
        # العنوان
        header_frame = ttk.Frame(main_frame)
        header_frame.pack(fill=tk.X, pady=(0, 20))
        
        ttk.Label(
            header_frame,
            text='إعدادات التطبيق',
            font=("IBM Plex Sans Arabic", 18, "bold"),
            bootstyle="primary"
        ).pack(anchor='center')

        # إطار الخيارات
        settings_frame = ttk.LabelFrame(
            main_frame,
            text='خيارات التحميل',
            padding=20,
            bootstyle="info"
        )
        settings_frame.pack(fill=tk.BOTH, expand=True)

        # جودة الفيديو
        self._create_setting_row(
            settings_frame,
            'جودة الفيديو:',
            self.quality_var,
            ['best', '1080p', '720p', '480p', '360p']
        )

        # صيغة الملف
        self._create_setting_row(
            settings_frame,
            'صيغة الملف:',
            self.format_var,
            ['mp4', 'mkv', 'webm', 'mp3', 'm4a']
        )

        # خيارات إضافية
        options_frame = ttk.Frame(settings_frame)
        options_frame.pack(fill=tk.X, pady=10)
        
        ttk.Checkbutton(
            options_frame,
            text='تحميل قائمة التشغيل',
            variable=self.playlist_var,
            bootstyle="round-toggle"
        ).pack(side=tk.LEFT, padx=5)
        
        ttk.Checkbutton(
            options_frame,
            text='تحميل الصوت فقط',
            variable=self.audio_only_var,
            command=self.toggle_audio_only,
            bootstyle="round-toggle"
        ).pack(side=tk.LEFT, padx=5)

        # مسار التحميل
        path_frame = ttk.Frame(settings_frame)
        path_frame.pack(fill=tk.X, pady=10)
        
        ttk.Label(
            path_frame,
            text='مسار التحميل:',
            font=("IBM Plex Sans Arabic", 10)
        ).pack(side=tk.LEFT, padx=5)
        
        ttk.Entry(
            path_frame,
            textvariable=self.location_var,
            bootstyle="primary"
        ).pack(side=tk.LEFT, fill=tk.X, expand=True, padx=5)
        
        ttk.Button(
            path_frame,
            text='تصفح',
            command=self.browse_location,
            bootstyle="info-outline"
        ).pack(side=tk.LEFT)

        # أزرار الحفظ والإلغاء
        buttons_frame = ttk.Frame(main_frame)
        buttons_frame.pack(fill=tk.X, pady=20)
        
        ttk.Button(
            buttons_frame,
            text='حفظ',
            command=self.save_settings,
            bootstyle="success-outline",
            width=15
        ).pack(side=tk.RIGHT, padx=5)
        
        ttk.Button(
            buttons_frame,
            text='إلغاء',
            command=self.destroy,
            bootstyle="danger-outline",
            width=15
        ).pack(side=tk.RIGHT, padx=5)

    def _create_setting_row(self, parent, label_text, variable, values):
        """دالة مساعدة لإنشاء صف إعدادات"""
        frame = ttk.Frame(parent)
        frame.pack(fill=tk.X, pady=5)
        
        ttk.Label(
            frame,
            text=label_text,
            font=("IBM Plex Sans Arabic", 10)
        ).pack(side=tk.LEFT, padx=5)
        
        ttk.Combobox(
            frame,
            textvariable=variable,
            values=values,
            bootstyle="primary",
            width=20
        ).pack(side=tk.LEFT, padx=5)

    def browse_location(self):
        directory = filedialog.askdirectory()
        if directory:
            self.location_var.set(directory)

    def toggle_audio_only(self):
        if self.audio_only_var.get():
            self.format_var.set('mp3')
            self.format_combo['values'] = ['mp3', 'm4a']
            self.quality_combo.configure(state='disabled')
        else:
            self.format_var.set('mp4')
            self.format_combo['values'] = ['mp4', 'mkv', 'webm', 'mp3', 'm4a']
            self.quality_combo.configure(state='normal')

    def save_settings(self):
        self.app.quality_var.set(self.quality_var.get())
        self.app.format_var.set(self.format_var.get())
        self.app.playlist_var.set(self.playlist_var.get())
        self.app.audio_only_var.set(self.audio_only_var.get())
        self.app.filename_var.set(self.filename_var.get())
        self.app.library_var.set(self.library_var.get())
        self.app.location_var.set(self.location_var.get())
        self.destroy()

class DeveloperInfoWindow(tk.Toplevel):
    def __init__(self, parent):
        super().__init__(parent)
        self.title('معلومات المطور - Developer Info')
        self.geometry('500x300')
        
        # تطبيق التصميم
        main_frame = ttk.Frame(self, bootstyle=PRIMARY)
        main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)
        
        # بطاقة معلومات المطور
        info_card = ttk.Frame(main_frame, bootstyle=LIGHT)
        info_card.pack(fill=tk.BOTH, expand=True, padx=2, pady=2)
        
        # صورة المطور (إذا وجدت)
        try:
            dev_image = Image.open('icons/developer.png')
            dev_image = dev_image.resize((100, 100), Image.Resampling.LANCZOS)
            dev_photo = ImageTk.PhotoImage(dev_image)
            image_label = ttk.Label(info_card, image=dev_photo)
            image_label.image = dev_photo
            image_label.pack(pady=10)
        except:
            pass  # تخطي إذا لم تكن الصورة موجودة

        # معلومات المطور
        dev_label = ttk.Label(
            info_card,
            text='تم تطوير البرنامج بواسطة الجيلاني\nDeveloped by Al-Jilani',
            font=("IBM Plex Sans Arabic", 14, "bold"),
            bootstyle=PRIMARY,
            justify='center'
        )
        dev_label.pack(pady=10)
        
        contact_label = ttk.Label(
            info_card,
            text='للتواصل - Contact Info:\nPhone: +967738977414\nEmail: dev@example.com',
            bootstyle=SECONDARY,
            justify='center'
        )
        contact_label.pack(pady=10)

        # زر الإغلاق
        close_btn = ttk.Button(
            info_card,
            text='إغلاق',
            command=self.destroy,
            bootstyle=(DANGER, "outline-toolbar"),
            padding=(20, 10)
        )
        close_btn.pack(pady=20)

class VideoDownloader:
    def __init__(self, root):
        self.root = root
        self.style = ttk.Style(theme='flatly')
        self.root.title('تحميل الفيديوهات - Video Downloader')
        self.root.geometry('1000x600')  # تحديث حجم النافذة
        
        # تعيين الحد الأدنى للحجم
        self.root.minsize(800, 600)
        
        # تعريف الألوان والخطوط
        self.PRIMARY = self.style.colors.primary
        self.SECONDARY = self.style.colors.secondary
        self.SUCCESS = self.style.colors.success
        self.FONT_FAMILY = "IBM Plex Sans Arabic"
        self.LARGE_FONT = (self.FONT_FAMILY, 16, "bold")
        self.MEDIUM_FONT = (self.FONT_FAMILY, 12, "bold")
        self.SMALL_FONT = (self.FONT_FAMILY, 10)
        
        # Initialize variables
        self.quality_var = tk.StringVar(value='best')
        self.format_var = tk.StringVar(value='mp4')
        self.playlist_var = tk.BooleanVar(value=False)
        self.audio_only_var = tk.BooleanVar(value=False)
        self.filename_var = tk.StringVar(value='%(title)s')
        self.library_var = tk.StringVar(value='ffmpeg')
        self.location_var = tk.StringVar(value=os.path.expanduser('~/Downloads'))

        # Initialize logger
        self.logger = Logger()
        
        # Store downloaded files history
        self.downloads_list = []
        self.download_history = None

        # إنشاء الإطار الرئيسي
        main_frame = ttk.Frame(root)
        main_frame.pack(fill=tk.BOTH, expand=True, padx=20, pady=20)

        # إنشاء البطاقات
        header_card = ttk.Frame(main_frame, bootstyle=self.PRIMARY)
        header_card.pack(fill=tk.X, pady=10)
        
        title_label = ttk.Label(
            header_card,
            text='تحميل الفيديوهات\nVideo Downloader',
            font=self.LARGE_FONT,
            justify='center'
        )
        title_label.pack(pady=10)

        # إطار الأزرار مع تصميم ttkbootstrap
        buttons_frame = ttk.Frame(main_frame)
        buttons_frame.pack(fill=tk.X, pady=10)
        
        self.settings_button = ttk.Button(
            buttons_frame,
            text='الإعدادات',
            command=self.open_settings,
            bootstyle=(self.SUCCESS, "outline-toolbar")
        )
        self.settings_button.pack(side='right', padx=5)
        
        self.history_button = ttk.Button(
            buttons_frame,
            text='سجل التحميل',
            command=self.open_history,
            bootstyle=(INFO, "outline-toolbar")
        )
        self.history_button.pack(side='right', padx=5)
        
        self.dev_button = ttk.Button(
            buttons_frame,
            text='المطور',
            command=self.show_developer_info,
            bootstyle=(self.SECONDARY, "outline-toolbar")
        )
        self.dev_button.pack(side='right', padx=5)

        # URL Input with Paste Button
        url_card = ttk.LabelFrame(
            main_frame,
            text='رابط الفيديو',
            padding=15,
            bootstyle=self.PRIMARY
        )
        url_card.pack(fill=tk.X, pady=10)
        
        url_input_frame = ttk.Frame(url_card)
        url_input_frame.pack(fill=tk.X, pady=5)
        
        self.url_entry = ttk.Entry(
            url_input_frame,
            font=self.MEDIUM_FONT,
            justify='right'
        )
        self.url_entry.pack(side='right', fill=tk.X, expand=True, padx=5)
        
        self.paste_button = ttk.Button(
            url_input_frame,
            text='لصق',
            command=self.paste_url,
            bootstyle=(INFO, "outline")
        )
        self.paste_button.pack(side='left')

        # Add supported sites frame
        sites_frame = ttk.LabelFrame(
            main_frame, 
            text='المواقع المدعومة - Supported Sites',
            padding=15,
            style='RTL.TLabelframe'
        )
        sites_frame.pack(fill=tk.X, pady=(0, 10), padx=15)

        # Create icons container
        icons_frame = ttk.Frame(sites_frame)
        icons_frame.pack(fill=tk.X, pady=5)

        # Center the icons
        center_frame = ttk.Frame(icons_frame)
        center_frame.pack(expand=True)
        
        # Load and display site icons
        self.site_icons = []
        sites_info = [
            ('youtube.png', 'YouTube'),
            ('facebook.png', 'Facebook'),
            ('twitter.png', 'Twitter'),
            ('tiktok.png', 'TikTok'),
            ('instagram.png', 'Instagram'),
            ('vimeo.png', 'Vimeo')
        ]
        
        # Create horizontal row of icons
        for i, (icon_file, site_name) in enumerate(sites_info):
            try:
                icon_path = os.path.join('icons', icon_file)
                if os.path.exists(icon_path):
                    img = Image.open(icon_path)
                    # Resize icon to 20x20 (even smaller)
                    img = img.resize((20, 20), Image.Resampling.LANCZOS)
                    icon = ImageTk.PhotoImage(img)
                    # Add icon with tooltip
                    icon_label = ttk.Label(center_frame, image=icon, cursor='hand2')
                    icon_label.pack(side=tk.LEFT, padx=10)
                    icon_label.bind('<Enter>', lambda e, name=site_name: self.show_site_name(e, name))
                    icon_label.bind('<Leave>', self.hide_site_name)
                    
                    # Keep reference to image
                    self.site_icons.append(icon)
                else:
                    self.logger.log_error(f"Icon file not found: {icon_path}")
            except Exception as e:
                self.logger.log_error(f"Error loading icon {icon_file}: {str(e)}")

        # Create tooltip label (hidden initially)
        self.tooltip = ttk.Label(
            self.root,
            background='#ffffe0',
            relief='solid',
            borderwidth=1
        )

        # Download Button with style
        self.download_button = ttk.Button(
            main_frame,
            text='تحميل',
            command=self.download_video,
            bootstyle=(SUCCESS, "outline"),  # تحديث نمط الزر
            padding=(20, 10)  # إضافة padding بدلاً من font
        )
        self.download_button.pack(pady=10)

        # Progress Label with correct style
        self.progress_label = ttk.Label(
            main_frame,
            text='',
            bootstyle=INFO
        )
        self.progress_label.pack(pady=5)

        # Add sanitize filename function
        def sanitize_filename(filename):
            try:
                # Remove emojis and special characters
                filename = filename.encode('ascii', 'ignore').decode('ascii')
                # Remove invalid characters
                filename = re.sub(r'[<>:"/\\|?*]', '', filename)
                # Replace multiple spaces with single space
                filename = ' '.join(filename.split())
                # Limit length
                if len(filename) > 200:
                    filename = filename[:197] + "..."
                return filename.strip() or "video"  # Default name if empty
            except Exception as e:
                self.logger.log_error(f"Filename sanitization error: {e}")
                return "video"  # Fallback filename
        
        self.sanitize_filename = sanitize_filename

    def open_settings(self):
        self.logger.log_info("Opening settings window")
        SettingsWindow(self.root, self)

    def open_history(self):
        self.logger.log_info("Opening download history window")
        if not self.download_history:
            self.download_history = DownloadHistoryWindow(self.root, self)
            # Populate with existing downloads
            for download in self.downloads_list:
                self.download_history.add_download(*download)
        elif self.download_history.winfo_exists():
            self.download_history.lift()
            self.download_history.focus_force()
        else:
            self.download_history = None
            self.open_history()

    def browse_location(self):
        directory = filedialog.askdirectory()
        if directory:
            self.location_var.set(directory)

    def download_video(self):
        url = self.url_entry.get().strip()
        if not url:
            self.logger.log_error("No URL provided")
            messagebox.showerror('خطأ - Error', 'الرجاء إدخال رابط الفيديو\nPlease enter a video URL')
            return

        try:
            self.logger.log_info(f"Starting download for URL: {url}")
            self.progress_label.config(text='جاري التحميل... Downloading...')
            self.root.update()

            self.downloaded_file_path = None

            def progress_hook(d):
                if d['status'] == 'downloading':
                    percent = d.get('_percent_str', '0%')
                    self.progress_label.config(text=f'جاري التحميل... Downloading: {percent}')
                    self.root.update()
                elif d['status'] == 'finished':
                    try:
                        outfile = d.get('filename')
                        if not outfile:
                            self.logger.log_error("No filename provided by downloader")
                            return
                        
                        # Get file parts
                        dirname = os.path.dirname(outfile)
                        basename = os.path.basename(outfile)
                        
                        # Sanitize only the name part
                        name, ext = os.path.splitext(basename)
                        safe_name = self.sanitize_filename(name)
                        safe_basename = f"{safe_name}{ext}"
                        new_path = os.path.join(dirname, safe_basename)
                        
                        # Handle duplicates
                        if os.path.exists(new_path) and outfile != new_path:
                            counter = 1
                            while os.path.exists(os.path.join(dirname, f"{safe_name}_{counter}{ext}")):
                                counter += 1
                            new_path = os.path.join(dirname, f"{safe_name}_{counter}{ext}")
                        
                        # Ensure the file exists before trying to rename
                        if os.path.exists(outfile):
                            if outfile != new_path:
                                os.rename(outfile, new_path)
                            self.downloaded_file_path = os.path.abspath(new_path)
                            self.logger.log_info(f"File saved as: {self.downloaded_file_path}")
                        else:
                            self.logger.log_error(f"Output file not found: {outfile}")
                    except Exception as e:
                        self.logger.log_error(f"Error in finished_hook: {str(e)}")
                        if 'outfile' in locals():
                            self.downloaded_file_path = outfile  # Use original path as fallback

            output_template = os.path.join(
                self.location_var.get(),
                '%(title)s.%(ext)s'
            )

            format_opt = 'bestaudio' if self.audio_only_var.get() else \
                      f'bestvideo[height<={self.quality_var.get()[:-1]}]+bestaudio/best' \
                      if self.quality_var.get() != 'best' else 'best'
            ydl_opts = {
                'format': format_opt,
                'merge_output_format': self.format_var.get(),
                'outtmpl': output_template,
                'progress_hooks': [progress_hook],  # Combined progress and finished hook
                'ffmpeg_location': 'ffmpeg',
                'prefer_ffmpeg': self.library_var.get() == 'ffmpeg',
                'extract_audio': self.audio_only_var.get(),
                'playlist': self.playlist_var.get(),
                'ignoreerrors': True,
                'no_warnings': False,
            }

            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                ydl.download([url])
                if self.download_history and self.download_history.winfo_exists():
                    # Add to history
                    if self.downloaded_file_path:
                        from datetime import datetime
                        filename = os.path.basename(self.downloaded_file_path)
                        download_info = (
                            filename,
                            self.downloaded_file_path,
                            datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                        )
                        self.downloads_list.append(download_info)
                        if self.download_history and self.download_history.winfo_exists():
                            self.download_history.add_download(*download_info)

            # Show success message with file path
            if self.downloaded_file_path:
                success_message = f'تم تحميل الفيديو بنجاح!\nVideo downloaded successfully!\n\nالمسار - Path:\n{self.downloaded_file_path}'
                messagebox.showinfo('نجاح - Success', success_message)
                
                # Open the containing folder
                if messagebox.askyesno('فتح المجلد - Open Folder', 
                                     'هل تريد فتح مجلد التحميل؟\nDo you want to open the download folder?'):
                    folder_path = os.path.dirname(self.downloaded_file_path)
                    if platform.system() == 'Windows':
                        os.startfile(folder_path)
                    elif platform.system() == 'Darwin':  # macOS
                        subprocess.run(['open', folder_path])
                    else:  # Linux
                        subprocess.run(['xdg-open', folder_path])

            self.progress_label.config(text='')
        except Exception as e:
            self.logger.log_error(e, url, "Error during download process")
            messagebox.showerror('خطأ - Error', f'حدث خطأ أثناء التحميل:\nError during download:\n{str(e)}')
            self.progress_label.config(text='')

    def paste_url(self):
        """دالة لصق الرابط من الحافظة"""
        try:
            # الحصول على محتوى الحافظة
            clipboard_content = self.root.clipboard_get()
            
            # تنظيف الرابط من المسافات الزائدة
            url = clipboard_content.strip()
            
            if url:
                # مسح المحتوى الحالي
                self.url_entry.delete(0, tk.END)
                # إدخال الرابط الجديد
                self.url_entry.insert(0, url)
                # تحريك المؤشر إلى نهاية النص
                self.url_entry.icursor(tk.END)
                # تحديد حقل الإدخال للتركيز
                self.url_entry.focus_set()
            else:
                self.logger.log_info("Empty clipboard content")
                messagebox.showwarning('تحذير - Warning', 'الحافظة فارغة\nClipboard is empty')
        except tk.TclError as e:
            self.logger.log_error(f"Clipboard error: {str(e)}")
            messagebox.showwarning('تحذير - Warning', 'الحافظة فارغة\nClipboard is empty')
        except Exception as e:
            self.logger.log_error(f"Paste error: {str(e)}")
            messagebox.showerror('خطأ - Error', f'حدث خطأ أثناء اللصق\nPaste error: {str(e)}')

    def toggle_audio_only(self):
        if self.audio_only_var.get():
            self.format_var.set('mp3')
            self.format_combo['values'] = ['mp3', 'm4a']
            self.quality_combo.configure(state='disabled')
        else:
            self.format_var.set('mp4')
            self.format_combo['values'] = ['mp4', 'mkv', 'webm', 'mp3', 'm4a']
            self.quality_combo.configure(state='normal')

    def show_developer_info(self):
        self.logger.log_info("Opening developer info window")
        DeveloperInfoWindow(self.root)

    def show_site_name(self, event, name):
        """Show tooltip with site name"""
        x = event.widget.winfo_rootx()
        y = event.widget.winfo_rooty() + 25
        self.tooltip.configure(text=name)
        self.tooltip.place(x=x, y=y)

    def hide_site_name(self, event):
        """Hide tooltip"""
        self.tooltip.place_forget()

if __name__ == '__main__':
    root = ttk.Window(themename="flatly")  # استخدام نافذة ttkbootstrap
    app = VideoDownloader(root)
    root.mainloop()
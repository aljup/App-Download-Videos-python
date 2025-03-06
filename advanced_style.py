from ttkthemes import ThemedStyle
import ttkbootstrap as ttk
from ttkbootstrap.constants import *

class AdvancedStyle:
    # Material Design Colors
    COLORS = {
        'primary': '#2196F3',
        'secondary': '#FF4081',
        'success': '#4CAF50',
        'info': '#00BCD4',
        'warning': '#FFC107',
        'danger': '#F44336',
        'light': '#F5F5F5',
        'dark': '#212121'
    }
    
    # Material Design Shadows
    SHADOWS = {
        'small': '0 2px 4px rgba(0,0,0,0.1)',
        'medium': '0 4px 8px rgba(0,0,0,0.1)',
        'large': '0 8px 16px rgba(0,0,0,0.1)'
    }

    @staticmethod
    def apply_advanced_style(root):
        # تطبيق ثيم متقدم
        style = ThemedStyle(root)
        style.set_theme("equilux")  # ثيم عصري داكن
        
        # تطبيق تصميم Bootstrap
        ttk.Style(theme='flatly')
        
        # تخصيص الأزرار
        style.configure('primary.TButton',
                       font=('IBM Plex Sans Arabic', 10),
                       background=AdvancedStyle.COLORS['primary'],
                       foreground='white',
                       padding=10,
                       relief='flat',
                       borderwidth=0)
        
        # تخصيص حقول الإدخال
        style.configure('modern.TEntry',
                       fieldbackground='white',
                       borderwidth=0,
                       relief='solid',
                       padding=8)
        
        # تخصيص البطاقات
        style.configure('card.TFrame',
                       background='white',
                       relief='solid',
                       borderwidth=1,
                       padding=15)
        
        # تخصيص العناوين
        style.configure('title.TLabel',
                       font=('IBM Plex Sans Arabic', 16, 'bold'),
                       foreground=AdvancedStyle.COLORS['primary'],
                       background='white',
                       padding=10)
        
        return style

    @staticmethod
    def create_gradient_button(parent, **kwargs):
        """إنشاء زر بتأثير متدرج"""
        button = ttk.Button(parent, style='primary.TButton', **kwargs)
        button.bind('<Enter>', lambda e: button.configure(style='accent.TButton'))
        button.bind('<Leave>', lambda e: button.configure(style='primary.TButton'))
        return button

    @staticmethod
    def create_hover_label(parent, **kwargs):
        """إنشاء تسمية مع تأثير التحويم"""
        label = ttk.Label(parent, **kwargs)
        label.bind('<Enter>', lambda e: label.configure(foreground=AdvancedStyle.COLORS['primary']))
        label.bind('<Leave>', lambda e: label.configure(foreground=AdvancedStyle.COLORS['dark']))
        return label

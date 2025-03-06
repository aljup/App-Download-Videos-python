from tkinter import ttk, font
import tkinter as tk
import os
from bootstrap_style import BootstrapStyle

class CustomStyle:
    # تحديث الألوان
    PRIMARY_COLOR = "#1976D2"  # أزرق داكن
    SECONDARY_COLOR = "#2196F3"  # أزرق فاتح
    ACCENT_COLOR = "#4CAF50"  # أخضر
    BG_COLOR = "#F5F5F5"  # رمادي فاتح للخلفية
    CARD_BG = "#FFFFFF"  # أبيض للبطاقات
    TEXT_COLOR = "#212121"  # أسود للنصوص
    HOVER_COLOR = "#42A5F5"  # لون التأثير عند المرور (أزرق فاتح)
    BORDER_COLOR = "#E0E0E0"  # لون الإطار

    # إضافة ألوان جديدة للحاوية
    CONTAINER_BG = "#F5F5F5"  # لون خلفية الحاوية

    # تحديث تعريفات الخطوط
    FONT_FAMILY = "IBM Plex Sans Arabic"  # الخط الرئيسي
    LARGE_FONT = ("IBM Plex Sans Arabic", 16, "bold")
    MEDIUM_FONT = ("IBM Plex Sans Arabic", 12, "bold")
    SMALL_FONT = ("IBM Plex Sans Arabic", 10)

    @staticmethod
    def load_custom_font():
        """تحميل وتسجيل الخط العربي"""
        try:
            # تحديد مسار الخط
            font_path = os.path.join(os.path.dirname(__file__), 'fonts', 'IBMPlexSansArabic-Regular.ttf')
            
            if os.path.exists(font_path):
                # تسجيل الخط في النظام
                from tkinter import font
                font.families()  # تحديث قائمة الخطوط
                font.Font(family="IBM Plex Sans Arabic", size=10)
                return True
            else:
                print(f"Font file not found at: {font_path}")
                return False
        except Exception as e:
            print(f"Error loading font: {e}")
            return False

    @staticmethod
    def apply_style():
        style = ttk.Style()
        
        # محاولة تحميل الخط العربي
        if CustomStyle.load_custom_font():
            font_family = "IBM Plex Sans Arabic"
        else:
            font_family = "Arial"  # خط بديل
        
        # تحديث تعريفات الخطوط
        CustomStyle.FONT_FAMILY = font_family
        CustomStyle.LARGE_FONT = (font_family, 16, "bold")
        CustomStyle.MEDIUM_FONT = (font_family, 12, "bold")
        CustomStyle.SMALL_FONT = (font_family, 10)

        style.theme_use('clam')  # استخدام ثيم نظيف كقاعدة

        # تطبيق أنماط Bootstrap
        btn_style = BootstrapStyle.apply_button_style(style, 'RTL.TButton')
        entry_style = BootstrapStyle.apply_entry_style(style, 'RTL.TEntry')
        frame_style = BootstrapStyle.apply_frame_style(style, 'RTL.TFrame')
        label_style = BootstrapStyle.apply_label_style(style, 'RTL.TLabel')
        card_style = BootstrapStyle.apply_card_style(style, 'Card.TFrame')

        # تخصيص أنماط إضافية
        style.configure('RTL.Header.TLabel',
                       font=CustomStyle.LARGE_FONT,
                       foreground=BootstrapStyle.PRIMARY,
                       background=BootstrapStyle.LIGHT,
                       padding=(0, BootstrapStyle.SPACING_3))

        style.configure('Download.TLabel',
                       background=BootstrapStyle.LIGHT,
                       foreground=BootstrapStyle.SUCCESS,
                       font=CustomStyle.MEDIUM_FONT)

        style.configure('Tooltip.TLabel',
                       background=BootstrapStyle.INFO,
                       foreground=BootstrapStyle.WHITE,
                       padding=BootstrapStyle.SPACING_2,
                       font=CustomStyle.SMALL_FONT)

        return style

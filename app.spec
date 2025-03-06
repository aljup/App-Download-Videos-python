import sys
from PyInstaller.building.build_main import Analysis, PYZ, EXE, COLLECT
import PyInstaller.config

block_cipher = None

a = Analysis(
    ['video_downloader.py'],
    pathex=[],
    binaries=[],
    datas=[
        ('icons/*.png', 'icons'),  # نسخ أيقونات التطبيق
        ('fonts/*.ttf', 'fonts'),  # نسخ الخطوط
    ],
    hiddenimports=['ttkbootstrap', 'PIL', 'yt_dlp'],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    [],
    exclude_binaries=True,
    name='Video Downloader',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    console=False,  # تعطيل نافذة وحدة التحكم
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
    icon='icons/app.ico',  # أيقونة التطبيق
)

coll = COLLECT(
    exe,
    a.binaries,
    a.zipfiles,
    a.datas,
    strip=False,
    upx=True,
    upx_exclude=[],
    name='Video Downloader',
)

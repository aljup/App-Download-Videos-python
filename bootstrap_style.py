class BootstrapStyle:
    # Bootstrap Colors
    PRIMARY = "#0d6efd"
    SECONDARY = "#6c757d"
    SUCCESS = "#198754"
    INFO = "#0dcaf0"
    WARNING = "#ffc107"
    DANGER = "#dc3545"
    LIGHT = "#f8f9fa"
    DARK = "#212529"
    WHITE = "#ffffff"
    
    # Bootstrap Shadows
    SHADOW_SM = "0 .125rem .25rem rgba(0,0,0,.075)"
    SHADOW = "0 .5rem 1rem rgba(0,0,0,.15)"
    SHADOW_LG = "0 1rem 3rem rgba(0,0,0,.175)"
    
    # Bootstrap Border Radius
    BORDER_RADIUS = 4
    BORDER_RADIUS_SM = 2
    BORDER_RADIUS_LG = 8
    
    # Bootstrap Spacing
    SPACING_1 = 4
    SPACING_2 = 8
    SPACING_3 = 16
    SPACING_4 = 24
    SPACING_5 = 48

    @staticmethod
    def apply_button_style(style, name='Bootstrap.TButton'):
        style.configure(name,
                      background=BootstrapStyle.PRIMARY,
                      foreground=BootstrapStyle.WHITE,
                      padding=(BootstrapStyle.SPACING_2, BootstrapStyle.SPACING_1),
                      relief='flat',
                      borderwidth=0)
        style.map(name,
                 background=[('active', BootstrapStyle.INFO),
                           ('pressed', BootstrapStyle.SECONDARY)])
        return name

    @staticmethod
    def apply_entry_style(style, name='Bootstrap.TEntry'):
        style.configure(name,
                      fieldbackground=BootstrapStyle.WHITE,
                      padding=BootstrapStyle.SPACING_2,
                      relief='solid',
                      borderwidth=1)
        return name

    @staticmethod
    def apply_frame_style(style, name='Bootstrap.TFrame'):
        style.configure(name,
                      background=BootstrapStyle.LIGHT,
                      relief='flat',
                      borderwidth=0)
        return name

    @staticmethod
    def apply_label_style(style, name='Bootstrap.TLabel'):
        style.configure(name,
                      background=BootstrapStyle.LIGHT,
                      foreground=BootstrapStyle.DARK,
                      padding=BootstrapStyle.SPACING_1)
        return name

    @staticmethod
    def apply_card_style(style, name='Bootstrap.Card.TFrame'):
        style.configure(name,
                      background=BootstrapStyle.WHITE,
                      relief='solid',
                      borderwidth=1,
                      padding=BootstrapStyle.SPACING_3)
        return name

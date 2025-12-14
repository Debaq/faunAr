import sys
import qrcode
from PIL import Image, ImageDraw, ImageFont
from PySide6.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, 
                               QHBoxLayout, QPushButton, QLineEdit, QLabel, 
                               QFileDialog, QComboBox, QMessageBox)
from PySide6.QtCore import Qt
from PySide6.QtGui import QPixmap
import os

class MarkerGenerator(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("FaunAR - Generador de Marcadores")
        self.setGeometry(100, 100, 800, 600)
        
        # Variable para imagen del animal
        self.animal_image_path = None
        
        # Widget central
        central_widget = QWidget()
        self.setCentralWidget(central_widget)
        layout = QVBoxLayout(central_widget)
        
        # URL del modelo
        url_layout = QHBoxLayout()
        url_label = QLabel("URL del modelo:")
        self.url_input = QLineEdit()
        self.url_input.setPlaceholderText("https://faunar.com/viewer.html?model=jaguar")
        url_layout.addWidget(url_label)
        url_layout.addWidget(self.url_input)
        layout.addLayout(url_layout)
        
        # Selector de icono predefinido
        icon_layout = QHBoxLayout()
        icon_label = QLabel("Icono predefinido:")
        self.icon_combo = QComboBox()
        self.icon_combo.addItems([
            "Ninguno",
            "🦁 León", 
            "🐆 Jaguar",
            "🐻 Oso",
            "🦅 Águila",
            "🦊 Zorro",
            "🦌 Ciervo",
            "🐺 Lobo"
        ])
        icon_layout.addWidget(icon_label)
        icon_layout.addWidget(self.icon_combo)
        layout.addLayout(icon_layout)
        
        # O cargar imagen custom
        image_layout = QHBoxLayout()
        self.load_image_btn = QPushButton("Cargar Imagen Custom")
        self.load_image_btn.clicked.connect(self.load_custom_image)
        self.image_label = QLabel("No se ha cargado imagen")
        image_layout.addWidget(self.load_image_btn)
        image_layout.addWidget(self.image_label)
        layout.addLayout(image_layout)
        
        # Preview
        self.preview_label = QLabel()
        self.preview_label.setAlignment(Qt.AlignCenter)
        self.preview_label.setMinimumHeight(400)
        self.preview_label.setStyleSheet("border: 2px solid #ccc; background: white;")
        layout.addWidget(self.preview_label)
        
        # Botones de acción
        button_layout = QHBoxLayout()
        
        self.generate_btn = QPushButton("Generar Preview")
        self.generate_btn.clicked.connect(self.generate_preview)
        
        self.save_btn = QPushButton("Guardar Marcador")
        self.save_btn.clicked.connect(self.save_marker)
        self.save_btn.setEnabled(False)
        
        button_layout.addWidget(self.generate_btn)
        button_layout.addWidget(self.save_btn)
        layout.addLayout(button_layout)
        
        # Variable para guardar el marcador generado
        self.current_marker = None
        
    def load_custom_image(self):
        file_path, _ = QFileDialog.getOpenFileName(
            self,
            "Seleccionar Imagen",
            "",
            "Imágenes (*.png *.jpg *.jpeg *.svg)"
        )
        
        if file_path:
            self.animal_image_path = file_path
            self.image_label.setText(f"✓ {os.path.basename(file_path)}")
            self.icon_combo.setCurrentIndex(0)  # Reset combo
    
    def generate_preview(self):
        url = self.url_input.text().strip()
        
        if not url:
            QMessageBox.warning(self, "Error", "Ingresa una URL válida")
            return
        
        try:
            # Generar el marcador
            marker_image = self.create_marker(url)
            self.current_marker = marker_image
            
            # Mostrar preview
            marker_image.save("temp_preview.png")
            pixmap = QPixmap("temp_preview.png")
            scaled_pixmap = pixmap.scaled(
                400, 400, 
                Qt.KeepAspectRatio, 
                Qt.SmoothTransformation
            )
            self.preview_label.setPixmap(scaled_pixmap)
            
            self.save_btn.setEnabled(True)
            
            QMessageBox.information(self, "Éxito", "Marcador generado correctamente")
            
        except Exception as e:
            QMessageBox.critical(self, "Error", f"Error generando marcador: {str(e)}")
    
    def create_marker(self, url):
        # Tamaño del marcador final
        marker_size = 1000
        border_size = 100  # Tamaño del borde negro
        inner_size = marker_size - (border_size * 2)
        
        # Crear imagen base con borde negro
        marker = Image.new('RGB', (marker_size, marker_size), 'black')
        inner = Image.new('RGB', (inner_size, inner_size), 'white')
        
        # Generar QR code
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_H,  # Alta corrección
            box_size=10,
            border=2,
        )
        qr.add_data(url)
        qr.make(fit=True)
        qr_img = qr.make_image(fill_color="black", back_color="white")
        
        # Redimensionar QR al 70% del área interna
        qr_size = int(inner_size * 0.7)
        qr_img = qr_img.resize((qr_size, qr_size), Image.Resampling.LANCZOS)
        
        # Pegar QR en el centro
        qr_pos = ((inner_size - qr_size) // 2, (inner_size - qr_size) // 2)
        inner.paste(qr_img, qr_pos)
        
        # Agregar icono del animal
        icon_image = self.get_animal_icon()
        if icon_image:
            # Tamaño del icono (30% del QR)
            icon_size = int(qr_size * 0.3)
            icon_image = icon_image.resize((icon_size, icon_size), Image.Resampling.LANCZOS)
            
            # Pegar icono en el centro del QR
            icon_pos = (
                (inner_size - icon_size) // 2,
                (inner_size - icon_size) // 2
            )
            
            # Crear fondo blanco para el icono
            icon_bg = Image.new('RGB', (icon_size, icon_size), 'white')
            inner.paste(icon_bg, icon_pos)
            
            # Si tiene transparencia, usar como máscara
            if icon_image.mode == 'RGBA':
                inner.paste(icon_image, icon_pos, icon_image)
            else:
                inner.paste(icon_image, icon_pos)
        
        # Pegar área interna en el marcador
        marker.paste(inner, (border_size, border_size))
        
        return marker
    
    def get_animal_icon(self):
        # Si hay imagen custom cargada
        if self.animal_image_path:
            try:
                return Image.open(self.animal_image_path).convert('RGBA')
            except:
                pass
        
        # Si hay icono predefinido seleccionado
        selected_icon = self.icon_combo.currentText()
        if selected_icon == "Ninguno":
            return None
        
        # Crear imagen con emoji
        icon_size = 200
        img = Image.new('RGBA', (icon_size, icon_size), (255, 255, 255, 0))
        draw = ImageDraw.Draw(img)
        
        # Extraer el emoji
        emoji = selected_icon.split()[0]
        
        try:
            # Intentar cargar una fuente que soporte emojis
            font_size = 150
            
            # Rutas comunes de fuentes emoji según el OS
            font_paths = [
                "/System/Library/Fonts/Apple Color Emoji.ttc",  # macOS
                "/usr/share/fonts/truetype/noto/NotoColorEmoji.ttf",  # Linux
                "C:\\Windows\\Fonts\\seguiemj.ttf",  # Windows
            ]
            
            font = None
            for font_path in font_paths:
                if os.path.exists(font_path):
                    try:
                        font = ImageFont.truetype(font_path, font_size)
                        break
                    except:
                        continue
            
            if not font:
                # Fallback a fuente por defecto
                font = ImageFont.load_default()
            
            # Calcular posición centrada
            bbox = draw.textbbox((0, 0), emoji, font=font)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]
            
            position = (
                (icon_size - text_width) // 2,
                (icon_size - text_height) // 2
            )
            
            draw.text(position, emoji, fill='black', font=font)
            
        except Exception as e:
            print(f"Error dibujando emoji: {e}")
            # Dibujar un círculo simple como fallback
            draw.ellipse([50, 50, 150, 150], fill='black')
        
        return img
    
    def save_marker(self):
        if not self.current_marker:
            return
        
        # Diálogo para guardar
        file_path, _ = QFileDialog.getSaveFileName(
            self,
            "Guardar Marcador",
            "marker.png",
            "PNG Images (*.png)"
        )
        
        if file_path:
            try:
                # Guardar imagen
                self.current_marker.save(file_path)
                
                # Generar también el archivo .patt
                patt_path = file_path.replace('.png', '.patt')
                self.generate_patt_file(self.current_marker, patt_path)
                
                QMessageBox.information(
                    self, 
                    "Éxito", 
                    f"Marcador guardado:\n{file_path}\n{patt_path}"
                )
                
            except Exception as e:
                QMessageBox.critical(self, "Error", f"Error guardando: {str(e)}")
    
    def generate_patt_file(self, marker_image, output_path):
        """
        Genera un archivo .patt básico para AR.js
        Nota: Este es un formato simplificado. Para producción,
        usar la herramienta oficial de AR.js es más confiable.
        """
        
        # Extraer el área interna (sin bordes)
        width, height = marker_image.size
        border = 100
        inner = marker_image.crop((border, border, width-border, height-border))
        
        # Redimensionar a 16x16 (tamaño estándar para patterns)
        inner = inner.convert('L')  # Escala de grises
        inner = inner.resize((16, 16), Image.Resampling.LANCZOS)
        
        # Convertir a matriz de valores
        pixels = list(inner.getdata())
        
        # Generar archivo .patt
        with open(output_path, 'w') as f:
            # AR.js espera 3 matrices (R, G, B) aunque sean iguales
            for color_channel in range(3):
                for i in range(16):
                    row = pixels[i*16:(i+1)*16]
                    # Invertir valores (0=negro, 255=blanco en .patt)
                    row_inverted = [255 - val for val in row]
                    line = ' '.join(map(str, row_inverted))
                    f.write(line + '\n')
                if color_channel < 2:
                    f.write('\n')

def main():
    app = QApplication(sys.argv)
    window = MarkerGenerator()
    window.show()
    sys.exit(app.exec())

if __name__ == "__main__":
    main()

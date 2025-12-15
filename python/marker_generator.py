import sys
import qrcode
from PIL import Image, ImageDraw, ImageFont
from PySide6.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout,
                               QHBoxLayout, QPushButton, QLineEdit, QLabel,
                               QFileDialog, QComboBox, QMessageBox, QListWidget,
                               QListWidgetItem, QGroupBox, QTextEdit, QSlider)
from PySide6.QtCore import Qt
from PySide6.QtGui import QPixmap, QColor
import os
import json
import subprocess
from pathlib import Path

class MarkerGenerator(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("FaunAR - Generador de Marcadores MindAR")
        self.setGeometry(100, 100, 1200, 700)

        # Variable para imagen del animal
        self.animal_image_path = None
        self.models_path = Path("../models")
        self.current_model = None

        # Variables para tamaños personalizables
        self.qr_size_percent = 90  # Porcentaje del área interna (90%)
        self.icon_scale = 100  # Escala de la imagen (100% = tamaño original)

        # Widget central
        central_widget = QWidget()
        self.setCentralWidget(central_widget)
        main_layout = QHBoxLayout(central_widget)

        # Panel izquierdo - Lista de modelos
        left_panel = self.create_models_panel()
        main_layout.addWidget(left_panel, 1)

        # Panel derecho - Generador
        right_panel = QWidget()
        layout = QVBoxLayout(right_panel)
        main_layout.addWidget(right_panel, 2)
        
        # URL del modelo
        url_layout = QHBoxLayout()
        url_label = QLabel("URL del modelo:")
        self.url_input = QLineEdit()
        self.url_input.setPlaceholderText("https://faunar.com/viewer.html?model=jaguar")
        url_layout.addWidget(url_label)
        url_layout.addWidget(self.url_input)
        layout.addLayout(url_layout)

        # Cargar imagen
        image_layout = QHBoxLayout()
        self.load_image_btn = QPushButton("📁 Cargar Imagen del Animal")
        self.load_image_btn.clicked.connect(self.load_custom_image)
        self.image_label = QLabel("⚠️ Debes cargar una imagen primero")
        self.image_label.setStyleSheet("color: #f44336; font-weight: bold;")
        image_layout.addWidget(self.load_image_btn)
        image_layout.addWidget(self.image_label)
        layout.addLayout(image_layout)

        # Separador
        separator = QLabel("─" * 50)
        separator.setStyleSheet("color: #ccc;")
        layout.addWidget(separator)

        # Control de tamaño del QR
        qr_size_group = QGroupBox("⚙️ Tamaño del QR")
        qr_size_layout = QVBoxLayout(qr_size_group)

        qr_size_info_layout = QHBoxLayout()
        qr_size_label = QLabel("Tamaño del QR (% del área interna):")
        self.qr_size_value_label = QLabel(f"{self.qr_size_percent}%")
        self.qr_size_value_label.setStyleSheet("font-weight: bold; color: #2196F3;")
        qr_size_info_layout.addWidget(qr_size_label)
        qr_size_info_layout.addWidget(self.qr_size_value_label)
        qr_size_info_layout.addStretch()
        qr_size_layout.addLayout(qr_size_info_layout)

        self.qr_size_slider = QSlider(Qt.Horizontal)
        self.qr_size_slider.setMinimum(50)  # 50%
        self.qr_size_slider.setMaximum(98)  # 98%
        self.qr_size_slider.setValue(self.qr_size_percent)
        self.qr_size_slider.setTickPosition(QSlider.TicksBelow)
        self.qr_size_slider.setTickInterval(10)
        self.qr_size_slider.valueChanged.connect(self.on_qr_size_changed)
        qr_size_layout.addWidget(self.qr_size_slider)

        layout.addWidget(qr_size_group)

        # Control de escala de la imagen
        icon_size_group = QGroupBox("⚙️ Tamaño de la Imagen (dentro del área fija)")
        icon_size_layout = QVBoxLayout(icon_size_group)

        icon_size_info_layout = QHBoxLayout()
        icon_size_label = QLabel("Tamaño de la imagen:")
        self.icon_size_value_label = QLabel(f"{self.icon_scale}%")
        self.icon_size_value_label.setStyleSheet("font-weight: bold; color: #4CAF50;")
        icon_size_info_layout.addWidget(icon_size_label)
        icon_size_info_layout.addWidget(self.icon_size_value_label)
        icon_size_info_layout.addStretch()
        icon_size_layout.addLayout(icon_size_info_layout)

        # Advertencia de cobertura
        self.coverage_warning = QLabel("✓ Área blanca fija: 30% del QR (no afecta lectura)")
        self.coverage_warning.setStyleSheet("color: #4CAF50; font-size: 10px;")
        icon_size_layout.addWidget(self.coverage_warning)

        self.icon_size_slider = QSlider(Qt.Horizontal)
        self.icon_size_slider.setMinimum(30)   # 30% escala
        self.icon_size_slider.setMaximum(200)  # 200% escala
        self.icon_size_slider.setValue(self.icon_scale)
        self.icon_size_slider.setTickPosition(QSlider.TicksBelow)
        self.icon_size_slider.setTickInterval(20)
        self.icon_size_slider.valueChanged.connect(self.on_icon_size_changed)
        icon_size_layout.addWidget(self.icon_size_slider)

        layout.addWidget(icon_size_group)

        # Preview
        self.preview_label = QLabel()
        self.preview_label.setAlignment(Qt.AlignCenter)
        self.preview_label.setMinimumHeight(400)
        self.preview_label.setStyleSheet("border: 2px solid #ccc; background: white;")
        layout.addWidget(self.preview_label)
        
        # Botón de acción
        button_layout = QHBoxLayout()

        self.save_btn = QPushButton("💾 Guardar Marcador")
        self.save_btn.clicked.connect(self.save_marker)
        self.save_btn.setEnabled(False)

        button_layout.addWidget(self.save_btn)
        layout.addLayout(button_layout)
        
        # Variable para guardar el marcador generado
        self.current_marker = None

        # Escanear modelos al iniciar
        self.scan_models()

    def create_models_panel(self):
        """Crea el panel izquierdo con la lista de modelos"""
        panel = QGroupBox("Modelos que necesitan marcadores MindAR")
        panel_layout = QVBoxLayout(panel)

        # Botón de actualizar
        refresh_btn = QPushButton("🔄 Actualizar Lista")
        refresh_btn.clicked.connect(self.scan_models)
        panel_layout.addWidget(refresh_btn)

        # Lista de modelos
        self.models_list = QListWidget()
        self.models_list.itemClicked.connect(self.on_model_selected)
        panel_layout.addWidget(self.models_list)

        # Info del modelo seleccionado
        self.model_info = QTextEdit()
        self.model_info.setReadOnly(True)
        self.model_info.setMaximumHeight(150)
        self.model_info.setPlaceholderText("Selecciona un modelo para ver detalles...")
        panel_layout.addWidget(self.model_info)

        # Botón para generar marcador del modelo seleccionado
        self.generate_for_model_btn = QPushButton("📋 Cargar modelo seleccionado")
        self.generate_for_model_btn.clicked.connect(self.load_selected_model)
        self.generate_for_model_btn.setEnabled(False)
        panel_layout.addWidget(self.generate_for_model_btn)

        return panel

    def scan_models(self):
        """Escanea la carpeta models/ y lista los que necesitan marcadores .mind"""
        self.models_list.clear()
        self.model_info.clear()
        self.current_model = None
        self.generate_for_model_btn.setEnabled(False)

        if not self.models_path.exists():
            item = QListWidgetItem(f"❌ No se encuentra: {self.models_path}")
            item.setBackground(QColor(255, 200, 200))
            self.models_list.addItem(item)
            return

        found_models = False
        models_data = []

        for model_folder in self.models_path.iterdir():
            if not model_folder.is_dir():
                continue

            config_path = model_folder / "config.json"
            if not config_path.exists():
                continue

            try:
                with open(config_path, 'r', encoding='utf-8') as f:
                    config = json.load(f)

                marker_config = config.get('marker', {})

                # Solo mostrar modelos que usen marcadores MindAR (image tracking)
                if not marker_config.get('enabled', False):
                    continue

                if marker_config.get('type', '') != 'image':
                    continue

                marker_file = marker_config.get('file', '')
                mind_path = model_folder / marker_file

                # Guardar datos del modelo
                model_data = {
                    'folder': model_folder.name,
                    'name': config.get('name', 'Sin nombre'),
                    'config': config,
                    'mind_file': marker_file,
                    'mind_path': mind_path,
                    'has_marker': mind_path.exists()
                }
                models_data.append(model_data)

            except Exception as e:
                print(f"Error procesando {model_folder.name}: {e}")

        # Ordenar: primero los que NO tienen marcador
        models_data.sort(key=lambda x: (x['has_marker'], x['name']))

        # Agregar a la lista
        for model_data in models_data:
            found_models = True
            status = "✅" if model_data['has_marker'] else "❌"
            item = QListWidgetItem(f"{status} {model_data['name']} ({model_data['folder']})")
            item.setData(Qt.UserRole, model_data)

            if not model_data['has_marker']:
                item.setBackground(QColor(255, 220, 220))
            else:
                item.setBackground(QColor(220, 255, 220))

            self.models_list.addItem(item)

        if not found_models:
            item = QListWidgetItem("ℹ️ No hay modelos que usen marcadores MindAR (.mind)")
            self.models_list.addItem(item)

    def on_model_selected(self, item):
        """Cuando se selecciona un modelo de la lista"""
        model_data = item.data(Qt.UserRole)

        if not model_data:
            return

        self.current_model = model_data
        self.generate_for_model_btn.setEnabled(True)

        # Mostrar info del modelo
        info_text = f"""📁 Carpeta: {model_data['folder']}
🏷️ Nombre: {model_data['name']}
📝 Nombre científico: {model_data['config'].get('scientificName', 'N/A')}

📄 Archivo necesario: {model_data['mind_file']}
📍 Ruta: {model_data['mind_path']}

Estado: {"✅ Tiene marcador MindAR" if model_data['has_marker'] else "❌ Falta generar marcador"}
"""
        self.model_info.setText(info_text)

    def load_selected_model(self):
        """Carga los datos del modelo seleccionado en el formulario"""
        if not self.current_model:
            return

        # Auto-completar URL
        model_id = self.current_model['folder']
        base_url = "https://faunar.com/viewer.html?model="
        self.url_input.setText(f"{base_url}{model_id}")

        # Generar preview automáticamente
        self.auto_generate_preview()

    def on_qr_size_changed(self, value):
        """Actualiza el tamaño del QR cuando cambia el slider"""
        self.qr_size_percent = value
        self.qr_size_value_label.setText(f"{value}%")
        self.auto_generate_preview()

    def on_icon_size_changed(self, value):
        """Actualiza la escala del icono cuando cambia el slider"""
        self.icon_scale = value
        self.icon_size_value_label.setText(f"{value}%")
        self.auto_generate_preview()

    def auto_generate_preview(self):
        """Genera preview automáticamente si hay URL configurada"""
        url = self.url_input.text().strip()
        if url:
            self.generate_preview()

    def update_coverage_warning(self, coverage_percent):
        """Actualiza la advertencia - el área siempre es fija al 30%"""
        self.coverage_warning.setText(f"✓ Área blanca fija: 30% del QR (no afecta lectura)")
        self.coverage_warning.setStyleSheet("color: #4CAF50; font-size: 10px;")

    def load_custom_image(self):
        file_path, _ = QFileDialog.getOpenFileName(
            self,
            "Seleccionar Imagen del Animal",
            "",
            "Imágenes (*.png *.jpg *.jpeg)"
        )

        if file_path:
            self.animal_image_path = file_path
            self.image_label.setText(f"✅ {os.path.basename(file_path)}")
            self.image_label.setStyleSheet("color: #4CAF50; font-weight: bold;")
            self.auto_generate_preview()

    def generate_preview(self):
        url = self.url_input.text().strip()

        if not url:
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

        except Exception as e:
            print(f"Error generando marcador: {str(e)}")
    
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
        
        # Redimensionar QR según el slider
        qr_size = int(inner_size * (self.qr_size_percent / 100))
        qr_img = qr_img.resize((qr_size, qr_size), Image.Resampling.LANCZOS)
        
        # Pegar QR en el centro
        qr_pos = ((inner_size - qr_size) // 2, (inner_size - qr_size) // 2)
        inner.paste(qr_img, qr_pos)
        
        # Agregar icono del animal
        icon_image = self.get_animal_icon()
        if icon_image:
            # ÁREA FIJA: 30% del QR (máximo seguro para lectura)
            area_size = int(qr_size * 0.30)

            # Tamaño del icono/imagen DENTRO del área (controlado por slider)
            # Al 100% llena el área completa, al 50% es la mitad, etc.
            icon_size = int(area_size * (self.icon_scale / 100))

            # Actualizar advertencia (el área siempre es 30%)
            self.update_coverage_warning(30.0)

            # Redimensionar icono manteniendo proporción
            icon_image = icon_image.resize((icon_size, icon_size), Image.Resampling.LANCZOS)

            # Posición del ÁREA FIJA en el centro del QR
            area_pos = (
                (inner_size - area_size) // 2,
                (inner_size - area_size) // 2
            )

            # Crear fondo blanco FIJO (30% del QR)
            icon_bg = Image.new('RGB', (area_size, area_size), 'white')
            inner.paste(icon_bg, area_pos)

            # Posición del ICONO centrado DENTRO del área fija
            icon_offset_x = (area_size - icon_size) // 2
            icon_offset_y = (area_size - icon_size) // 2
            icon_pos = (
                area_pos[0] + icon_offset_x,
                area_pos[1] + icon_offset_y
            )

            # Pegar icono dentro del área
            if icon_image.mode == 'RGBA':
                inner.paste(icon_image, icon_pos, icon_image)
            else:
                inner.paste(icon_image, icon_pos)
        
        # Pegar área interna en el marcador
        marker.paste(inner, (border_size, border_size))
        
        return marker
    
    def get_animal_icon(self):
        # Solo cargar imagen si está disponible
        if self.animal_image_path:
            try:
                return Image.open(self.animal_image_path).convert('RGBA')
            except Exception as e:
                print(f"Error cargando imagen: {e}")
                return None

        return None
    
    def save_marker(self):
        if not self.current_marker:
            return

        # Determinar nombre y ruta por defecto
        if self.current_model:
            default_name = self.current_model['mind_file'].replace('.mind', '.png')
            default_dir = str(self.current_model['mind_path'].parent)
            default_path = f"{default_dir}/{default_name}"
        else:
            default_path = "marker.png"

        # Diálogo para guardar
        file_path, _ = QFileDialog.getSaveFileName(
            self,
            "Guardar Marcador",
            default_path,
            "PNG Images (*.png)"
        )

        if file_path:
            try:
                # Guardar imagen del marcador completo (con QR y borde)
                self.current_marker.save(file_path)
                print(f"✅ Imagen del marcador guardada: {file_path}")

                # Intentar generar el archivo .mind
                mind_path = file_path.replace('.png', '.mind')
                self.generate_mind_file(file_path, mind_path)

                # Actualizar lista de modelos
                self.scan_models()

                QMessageBox.information(
                    self,
                    "Marcador Guardado",
                    f"✅ Imagen guardada: {os.path.basename(file_path)}\n\n"
                    f"Verifica la consola para el estado del archivo .mind"
                )

            except Exception as e:
                QMessageBox.critical(
                    self,
                    "Error",
                    f"Error guardando marcador: {str(e)}"
                )
                print(f"❌ Error guardando: {str(e)}")

    def generate_mind_file(self, image_path, output_path):
        """
        Intenta generar un archivo .mind usando el compilador de MindAR
        """
        print("\n🔧 Intentando generar archivo .mind...")

        # Verificar si el compilador está instalado
        try:
            result = subprocess.run(
                ['mind-ar-js-compiler', '--help'],
                capture_output=True,
                text=True,
                timeout=5
            )

            if result.returncode != 0:
                raise Exception("Compilador no responde correctamente")

        except FileNotFoundError:
            print("❌ Compilador MindAR no encontrado")
            print("📌 Instala con: npm install -g mind-ar-js-compiler")
            print(f"📌 Luego ejecuta: mind-ar-js-compiler {image_path} {output_path}")
            return
        except Exception as e:
            print(f"❌ Error verificando compilador: {e}")
            print(f"📌 Ejecuta manualmente: mind-ar-js-compiler {image_path} {output_path}")
            return

        # Ejecutar el compilador
        try:
            print(f"⏳ Compilando {os.path.basename(image_path)} → {os.path.basename(output_path)}...")

            result = subprocess.run(
                ['mind-ar-js-compiler', image_path, output_path],
                capture_output=True,
                text=True,
                timeout=60  # Timeout de 60 segundos
            )

            if result.returncode == 0:
                print(f"✅ Archivo .mind generado exitosamente: {output_path}")
                if result.stdout:
                    print(f"   {result.stdout}")
            else:
                print(f"❌ Error al compilar:")
                print(f"   {result.stderr}")
                print(f"📌 Intenta manualmente: mind-ar-js-compiler {image_path} {output_path}")

        except subprocess.TimeoutExpired:
            print("❌ Timeout: La compilación tardó demasiado")
            print(f"📌 Intenta manualmente: mind-ar-js-compiler {image_path} {output_path}")
        except Exception as e:
            print(f"❌ Error ejecutando compilador: {e}")
            print(f"📌 Intenta manualmente: mind-ar-js-compiler {image_path} {output_path}")

def main():
    app = QApplication(sys.argv)
    window = MarkerGenerator()
    window.show()
    sys.exit(app.exec())

if __name__ == "__main__":
    main()

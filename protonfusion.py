import pygame
import numpy as np
import json
import math
import sys
import random
from pygame import gfxdraw

# ======================
# Load periodic table data
with open('periodic_table.json') as f:  # Assuming data is in a separate file
    periodic_table = json.load(f)

# ======================
# Game Setup
pygame.init()
pygame.mixer.init()
screen = pygame.display.set_mode((1024, 768), pygame.RESIZABLE)
pygame.display.set_caption("Proton Fusion Drift - Chemistry Visualization")
clock = pygame.time.Clock()

# Load fonts
try:
    title_font = pygame.font.Font(None, 48)
    element_font = pygame.font.Font(None, 72)
    info_font = pygame.font.Font(None, 28)
    small_font = pygame.font.Font(None, 22)
except:
    print("Font loading failed, using system defaults")
    title_font = pygame.font.SysFont("Arial", 48)
    element_font = pygame.font.SysFont("Arial", 72)
    info_font = pygame.font.SysFont("Arial", 28)
    small_font = pygame.font.SysFont("Arial", 22)

# Colors
BACKGROUND = (10, 10, 30)
TEXT_COLOR = (220, 220, 255)
HIGHLIGHT = (255, 255, 150)
ELECTRON_COLORS = [
    (255, 100, 100),  # Red
    (100, 255, 100),  # Green
    (100, 100, 255),  # Blue
    (255, 255, 100),  # Yellow
    (255, 100, 255),  # Magenta
    (100, 255, 255),  # Cyan
]

# Game state
class GameState:
    def __init__(self):
        self.selected_element_key = "1"  # Start with Hydrogen
        self.current_element = periodic_table[self.selected_element_key]
        self.animation_time = 0
        self.electron_positions = []
        self.show_info = True
        self.show_orbitals = True
        self.show_electrons = True
        self.zoom = 1.0
        self.particles = []
        self.last_particle_time = 0
        self.camera_offset = [0, 0]
        self.dragging = False
        self.last_mouse_pos = (0, 0)
        
game_state = GameState()

# ======================
# Sound Generation
def generate_tone(frequency, duration=0.5, volume=0.3, sample_rate=44100, wave_type='sine'):
    t = np.linspace(0, duration, int(sample_rate * duration), False)
    
    if wave_type == 'sine':
        tone = np.sin(frequency * t * 2 * np.pi)
    elif wave_type == 'square':
        tone = np.sign(np.sin(frequency * t * 2 * np.pi))
    elif wave_type == 'sawtooth':
        tone = 2 * (t * frequency - np.floor(0.5 + t * frequency))
    elif wave_type == 'triangle':
        tone = 2 * np.abs(2 * (t * frequency - np.floor(t * frequency + 0.5))) - 1
    
    # Apply envelope
    envelope = np.ones_like(t)
    attack = int(0.05 * sample_rate)
    release = int(0.2 * sample_rate)
    if attack > 0:
        envelope[:attack] = np.linspace(0, 1, attack)
    if release > 0:
        envelope[-release:] = np.linspace(1, 0, release)
    
    tone = tone * envelope
    tone = (tone * (2**15 - 1) * volume).astype(np.int16)
    sound = pygame.sndarray.make_sound(tone)
    return sound

def play_element_sound(element):
    gyromagnetic = abs(element['nmr_data'].get('gyromagnetic_ratio', 10))
    if gyromagnetic is None:
        gyromagnetic = 10
    
    # Base frequency scales with atomic number
    base_freq = 220 + (element['atomic_number'] * 5)
    
    # Modulate with gyromagnetic ratio
    frequency = base_freq * (1 + gyromagnetic / 100)
    
    # Choose wave type based on element group
    atomic_number = element['atomic_number']
    if atomic_number <= 2:  # H, He
        wave_type = 'sine'
    elif atomic_number <= 10:  # First period
        wave_type = 'triangle'
    elif atomic_number <= 18:  # Second period
        wave_type = 'square'
    else:
        wave_type = 'sawtooth'
    
    duration = 0.3 + (0.7 * (1 - (atomic_number % 10)/10))
    tone = generate_tone(frequency, duration, 0.3, wave_type=wave_type)
    tone.play()

# ======================
# Particle System
class Particle:
    def __init__(self, x, y, element):
        self.x = x
        self.y = y
        self.size = random.randint(2, 5)
        self.color = get_element_color(element)
        self.life = 100
        self.velocity = [random.uniform(-1, 1), random.uniform(-1, 1)]
        
    def update(self):
        self.x += self.velocity[0]
        self.y += self.velocity[1]
        self.life -= 1
        self.size = max(0, self.size - 0.05)
        
    def draw(self, surface):
        alpha = min(255, self.life * 2.55)
        color = (*self.color[:3], alpha)
        pygame.gfxdraw.filled_circle(surface, int(self.x), int(self.y), int(self.size), color)

def get_element_color(element):
    # Color based on element category
    atomic_number = element['atomic_number']
    
    if atomic_number == 1:  # Hydrogen
        return (200, 200, 255, 200)
    elif 2 <= atomic_number <= 10:  # Noble gases
        return (200, 255, 200, 200)
    elif 11 <= atomic_number <= 18:  # Alkali/alkaline earth
        return (255, 200, 200, 200)
    elif 19 <= atomic_number <= 36:  # Transition metals
        return (255, 255, 150, 200)
    elif 37 <= atomic_number <= 54:  # Other metals
        return (200, 255, 255, 200)
    else:  # Lanthanides/Actinides
        return (255, 200, 255, 200)

def update_particles():
    current_time = pygame.time.get_ticks()
    if current_time - game_state.last_particle_time > 100:  # Add new particles every 100ms
        game_state.particles.append(Particle(
            random.randint(100, 924),
            random.randint(100, 668),
            game_state.current_element
        ))
        game_state.last_particle_time = current_time
    
    # Update existing particles
    for particle in game_state.particles[:]:
        particle.update()
        if particle.life <= 0:
            game_state.particles.remove(particle)

# ======================
# Visualization
def calculate_electron_positions(atomic_number, time):
    positions = []
    if atomic_number == 0:
        return positions
    
    # Simple orbital model
    shell_config = [2, 8, 8, 18, 18, 32]  # Electrons per shell
    remaining_electrons = atomic_number
    
    for shell, max_electrons in enumerate(shell_config, 1):
        if remaining_electrons <= 0:
            break
        
        electrons_in_shell = min(max_electrons, remaining_electrons)
        remaining_electrons -= electrons_in_shell
        
        radius = 30 + shell * 25
        angle_step = (2 * math.pi) / max(1, electrons_in_shell)
        
        for i in range(electrons_in_shell):
            angle = angle_step * i + (time * 0.0005 * shell)
            x = math.cos(angle) * radius
            y = math.sin(angle) * radius
            positions.append((x, y, shell-1))  # shell-1 for color index
            
    return positions

def draw_element_visual(screen, element, center_x, center_y, time):
    atomic_number = element.get('atomic_number', 1)
    electronegativity = element.get('electronegativity', 1.0) or 1.0
    atomic_mass = element.get('atomic_mass', 1.0) or 1.0
    
    # Calculate nucleus properties
    nucleus_radius = 10 + min(20, math.log(atomic_mass) * 2)
    proton_count = atomic_number
    neutron_count = max(0, round(atomic_mass) - proton_count)
    
    # Color based on electronegativity
    red = int(min(255, electronegativity * 60))
    blue = 255 - red
    nucleus_color = (red, 50, blue, 200)
    
    # Draw orbitals if enabled
    if game_state.show_orbitals:
        shell_config = [2, 8, 8, 18, 18, 32]
        remaining_electrons = atomic_number
        
        for shell, max_electrons in enumerate(shell_config, 1):
            if remaining_electrons <= 0:
                break
            
            radius = 30 + shell * 25
            alpha = min(255, 50 + (shell * 30))
            orbital_color = (red, 50, blue, alpha)
            pygame.gfxdraw.circle(screen, center_x, center_y, int(radius * game_state.zoom), orbital_color)
    
    # Draw nucleus with proton/neutron representation
    pygame.gfxdraw.filled_circle(screen, center_x, center_y, int(nucleus_radius * game_state.zoom), nucleus_color)
    
    # Draw nucleons (simplified)
    nucleon_radius = max(2, nucleus_radius / 5)
    for i in range(proton_count + neutron_count):
        if i >= 20:  # Don't draw too many nucleons
            break
            
        angle = (2 * math.pi * i) / max(1, proton_count + neutron_count)
        distance = random.uniform(0, nucleus_radius * 0.7)
        x = center_x + math.cos(angle) * distance * game_state.zoom
        y = center_y + math.sin(angle) * distance * game_state.zoom
        
        if i < proton_count:
            pygame.gfxdraw.filled_circle(screen, int(x), int(y), int(nucleon_radius), (255, 100, 100, 200))
        else:
            pygame.gfxdraw.filled_circle(screen, int(x), int(y), int(nucleon_radius), (200, 200, 255, 200))
    
    # Draw electrons if enabled
    if game_state.show_electrons:
        game_state.electron_positions = calculate_electron_positions(atomic_number, time)
        for x, y, shell in game_state.electron_positions:
            color_idx = shell % len(ELECTRON_COLORS)
            color = ELECTRON_COLORS[color_idx]
            pygame.gfxdraw.filled_circle(
                screen, 
                center_x + int(x * game_state.zoom), 
                center_y + int(y * game_state.zoom), 
                4, 
                color
            )

def draw_element_info(screen, element, x, y):
    if not game_state.show_info:
        return
    
    # Main element info
    symbol_text = element_font.render(element['symbol'], True, HIGHLIGHT)
    name_text = title_font.render(element['name'], True, TEXT_COLOR)
    number_text = info_font.render(f"Atomic Number: {element['atomic_number']}", True, TEXT_COLOR)
    mass_text = info_font.render(f"Atomic Mass: {element.get('atomic_mass', 'N/A')}", True, TEXT_COLOR)
    
    screen.blit(symbol_text, (x, y))
    screen.blit(name_text, (x, y + 80))
    screen.blit(number_text, (x, y + 140))
    screen.blit(mass_text, (x, y + 170))
    
    # Additional properties
    y_offset = 210
    properties = [
        f"Electronegativity: {element.get('electronegativity', 'N/A')}",
        f"Melting Point: {element.get('melting_point', 'N/A')} K",
        f"Boiling Point: {element.get('boiling_point', 'N/A')} K",
        f"Density: {element.get('density', 'N/A')} g/cm³"
    ]
    
    for prop in properties:
        prop_text = info_font.render(prop, True, TEXT_COLOR)
        screen.blit(prop_text, (x, y + y_offset))
        y_offset += 30
    
    # NMR data if available
    nmr_data = element.get('nmr_data', {})
    if nmr_data:
        nmr_title = info_font.render("NMR Properties:", True, HIGHLIGHT)
        screen.blit(nmr_title, (x, y + y_offset))
        y_offset += 30
        
        nmr_props = [
            f"Spin: {nmr_data.get('spin', 'N/A')}",
            f"Gyromagnetic Ratio: {nmr_data.get('gyromagnetic_ratio', 'N/A')}",
            f"Chemical Shift: {nmr_data.get('chemical_shift', 'N/A')}"
        ]
        
        for prop in nmr_props:
            prop_text = small_font.render(prop, True, TEXT_COLOR)
            screen.blit(prop_text, (x + 10, y + y_offset))
            y_offset += 25

def draw_controls(screen):
    controls = [
        "Controls:",
        "Space - Next Element",
        "Left/Right - Navigate Elements",
        "I - Toggle Info",
        "O - Toggle Orbitals",
        "E - Toggle Electrons",
        "Mouse Wheel - Zoom",
        "Mouse Drag - Move View",
        "R - Reset View"
    ]
    
    for i, control in enumerate(controls):
        color = HIGHLIGHT if i == 0 else TEXT_COLOR
        text = small_font.render(control, True, color)
        screen.blit(text, (20, 20 + i * 25))

# ======================
# Game Loop
running = True
while running:
    current_time = pygame.time.get_ticks()
    screen.fill(BACKGROUND)
    
    # Handle events
    for event in pygame.event.get():
        if event.type == pygame.QUIT:
            running = False
        
        elif event.type == pygame.KEYDOWN:
            # Navigation
            if event.key == pygame.K_SPACE or event.key == pygame.K_RIGHT:
                # Next element
                current_num = int(game_state.selected_element_key)
                game_state.selected_element_key = str((current_num % len(periodic_table)) + 1)
                game_state.current_element = periodic_table[game_state.selected_element_key]
                play_element_sound(game_state.current_element)
                
            elif event.key == pygame.K_LEFT:
                # Previous element
                current_num = int(game_state.selected_element_key)
                game_state.selected_element_key = str((current_num - 2) % len(periodic_table) + 1)
                game_state.current_element = periodic_table[game_state.selected_element_key]
                play_element_sound(game_state.current_element)
                
            # Toggles
            elif event.key == pygame.K_i:
                game_state.show_info = not game_state.show_info
            elif event.key == pygame.K_o:
                game_state.show_orbitals = not game_state.show_orbitals
            elif event.key == pygame.K_e:
                game_state.show_electrons = not game_state.show_electrons
            elif event.key == pygame.K_r:
                # Reset view
                game_state.zoom = 1.0
                game_state.camera_offset = [0, 0]
            
        elif event.type == pygame.MOUSEBUTTONDOWN:
            if event.button == 1:  # Left mouse button
                game_state.dragging = True
                game_state.last_mouse_pos = event.pos
                
        elif event.type == pygame.MOUSEBUTTONUP:
            if event.button == 1:
                game_state.dragging = False
                
        elif event.type == pygame.MOUSEMOTION:
            if game_state.dragging:
                dx = event.pos[0] - game_state.last_mouse_pos[0]
                dy = event.pos[1] - game_state.last_mouse_pos[1]
                game_state.camera_offset[0] += dx
                game_state.camera_offset[1] += dy
                game_state.last_mouse_pos = event.pos
                
        elif event.type == pygame.MOUSEWHEEL:
            # Zoom in/out
            zoom_factor = 1.1 if event.y > 0 else 0.9
            game_state.zoom = max(0.5, min(2.0, game_state.zoom * zoom_factor))
    
    # Update particles
    update_particles()
    
    # Calculate center with camera offset
    center_x = screen.get_width() // 2 + game_state.camera_offset[0]
    center_y = screen.get_height() // 2 + game_state.camera_offset[1]
    
    # Draw particles
    for particle in game_state.particles:
        particle.draw(screen)
    
    # Draw element visualization
    draw_element_visual(screen, game_state.current_element, center_x, center_y, current_time)
    
    # Draw element info
    draw_element_info(screen, game_state.current_element, 50, 200)
    
    # Draw controls
    draw_controls(screen)
    
    # Draw current element number
    status_text = small_font.render(
        f"Element {game_state.current_element['atomic_number']} of {len(periodic_table)}", 
        True, 
        TEXT_COLOR
    )
    screen.blit(status_text, (screen.get_width() - 200, 20))
    
    pygame.display.flip()
    clock.tick(60)

pygame.quit()
sys.exit()
#!/usr/bin/env python3
"""
Proton Fusion Drift - Quantum Evolution Edition
Doctor Solen DriftCore
"""

import pygame
import numpy as np
import json
import math
import sys
import random
import os
from pygame import gfxdraw
from typing import Dict, List, Tuple, Set, Optional

# ======================
# Constants
SCREEN_WIDTH, SCREEN_HEIGHT = 1024, 768
FPS = 60
BG_ALPHA = 180
GRID_ALPHA = 120
MAX_TRANSITION_TIME = 1000  # ms

# ======================
# Load periodic table data with error handling
try:
    with open('periodic_table.json') as f:
        periodic_table: Dict[str, Dict] = json.load(f)
    if not any(e.get('atomic_number') == 1 for e in periodic_table.values()):
        raise ValueError("JSON must contain at least Hydrogen (atomic_number 1)")
except (FileNotFoundError, json.JSONDecodeError, ValueError) as e:
    print(f"Error loading periodic table: {e}")
    print("Creating minimal default table with just Hydrogen")
    periodic_table = {
        "0": {
            "name": "Hydrogen",
            "symbol": "H",
            "atomic_number": 1,
            "atomic_mass": 1.008,
            "electronegativity": 2.20,
            "melting_point": 14.01,
            "boiling_point": 20.28,
            "density": 0.00008988,
            "nmr_data": {
                "spin": "1/2",
                "gyromagnetic_ratio": 26.752,
                "chemical_shift": "0.0"
            }
        }
    }

# ======================
# Game Setup
pygame.init()
pygame.mixer.init()
screen = pygame.display.set_mode((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.RESIZABLE)
pygame.display.set_caption("Proton Fusion Drift - Quantum Evolution Edition")
clock = pygame.time.Clock()

# Load fonts
try:
    title_font = pygame.font.Font(None, 48)
    element_font = pygame.font.Font(None, 72)
    info_font = pygame.font.Font(None, 28)
    small_font = pygame.font.Font(None, 22)
    grid_font = pygame.font.Font(None, 18)
except:
    print("Font loading failed, using system defaults")
    title_font = pygame.font.SysFont("Arial", 48)
    element_font = pygame.font.SysFont("Arial", 72)
    info_font = pygame.font.SysFont("Arial", 28)
    small_font = pygame.font.SysFont("Arial", 22)
    grid_font = pygame.font.SysFont("Arial", 18)

# Colors
BACKGROUND = (10, 10, 30)
TEXT_COLOR = (220, 220, 255)
HIGHLIGHT = (255, 255, 150)
AGAPE_COLORS = {
    'Hydrogen': (255, 255, 0),
    'Helium': (0, 255, 200),
    'Lithium': (51, 153, 255),
    'Entropy': (255, 0, 51),
    'AGAPE': (204, 102, 255)
}
ELECTRON_COLORS = [
    (255, 100, 100),
    (100, 255, 100),
    (100, 100, 255),
    (255, 255, 100),
    (255, 100, 255),
    (100, 255, 255),
]

# Quantum grid colors
GRID_COLORS = [
    (50, 50, 80, 100),
    (80, 50, 80, 120),
    (50, 80, 80, 120),
    (80, 80, 50, 120)
]

# ======================
# Dynamic Background System
class DynamicBackground:
    def __init__(self):
        self.backgrounds: List[pygame.Surface] = []
        self.current_bg = 0
        self.bg_offset = [0, 0]
        self.bg_speed = 0.5
        self.load_backgrounds()
        self.bg_surface = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.SRCALPHA)
        self.bg_distortion = 0.0
        self.bg_distortion_speed = 0.02
        self.bg_alpha = BG_ALPHA
        
    def load_backgrounds(self) -> None:
        try:
            default_bg = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT))
            default_bg.fill((20, 10, 40))
            
            for x in range(0, SCREEN_WIDTH, 5):
                for y in range(0, SCREEN_HEIGHT, 5):
                    intensity = random.randint(10, 30)
                    default_bg.set_at((x, y), (intensity, intensity//2, intensity*2))
            
            self.backgrounds.append(default_bg)
            
            for i in range(3):
                bg = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT))
                bg.fill((10+i*5, 20-i*3, 30+i*2))
                
                for x in range(0, SCREEN_WIDTH, 10):
                    for y in range(0, SCREEN_HEIGHT, 10):
                        if (x + y) % 40 < 20 or abs(x - y) % 40 < 20:
                            intensity = 50 + (x + y) % 50
                            r = min(255, intensity + random.randint(-20, 20))
                            g = min(255, intensity//2 + random.randint(-10, 10))
                            b = min(255, intensity*2 + random.randint(-20, 20))
                            pygame.draw.circle(bg, (r, g, b), (x, y), 2 + (x*y) % 3)
                
                self.backgrounds.append(bg)
                
        except Exception as e:
            print(f"Background loading error: {e}")
            bg = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT))
            bg.fill((20, 10, 40))
            self.backgrounds.append(bg)
    
    def update(self) -> None:
        self.bg_offset[0] += self.bg_speed
        self.bg_offset[1] += self.bg_speed * 0.7
        self.bg_distortion += self.bg_distortion_speed
        
        if self.bg_offset[0] > SCREEN_WIDTH:
            self.bg_offset[0] -= SCREEN_WIDTH
        if self.bg_offset[1] > SCREEN_HEIGHT:
            self.bg_offset[1] -= SCREEN_HEIGHT
    
    def draw(self, surface: pygame.Surface) -> None:
        bg = self.backgrounds[self.current_bg % len(self.backgrounds)]
        temp_surface = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.SRCALPHA)
        
        for x in range(-1, 2):
            for y in range(-1, 2):
                pos_x = x * SCREEN_WIDTH + self.bg_offset[0]
                pos_y = y * SCREEN_HEIGHT + self.bg_offset[1]
                distort_x = math.sin(self.bg_distortion + x * 0.5) * 5
                distort_y = math.cos(self.bg_distortion + y * 0.5) * 5
                temp_surface.blit(bg, (pos_x + distort_x, pos_y + distort_y))
        
        temp_surface.set_alpha(self.bg_alpha)
        surface.blit(temp_surface, (0, 0))
        
        overlay = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.SRCALPHA)
        overlay.fill((0, 0, 0, 100))
        surface.blit(overlay, (0, 0))

# ======================
# Quantum Puzzle Grid System
class QuantumGrid:
    def __init__(self, width: int = 10, height: int = 8):
        self.width = width
        self.height = height
        self.cell_size = 80
        self.grid_offset = [50, 100]
        self.cells: List[Dict] = []
        self.initialize_grid()
        self.active_cells: Set[Tuple[int, int]] = set()
        self.pulse_phase = 0.0
        self.pulse_speed = 0.05
        self.grid_alpha = GRID_ALPHA
        self.unlocked_cells = 1
        
    def initialize_grid(self) -> None:
        for x in range(self.width):
            for y in range(self.height):
                cell = {
                    'x': x,
                    'y': y,
                    'color': random.choice(GRID_COLORS),
                    'energy': random.uniform(0.5, 1.5),
                    'phase': random.uniform(0, 2 * math.pi),
                    'locked': True,
                    'activation_time': 0
                }
                self.cells.append(cell)
        
        center_x, center_y = self.width // 2, self.height // 2
        self.cells[center_y * self.width + center_x]['locked'] = False
    
    def update(self, current_element: Dict, time: int) -> None:
        self.pulse_phase = (self.pulse_phase + self.pulse_speed) % (2 * math.pi)
        atomic_number = current_element.get('atomic_number', 1)
        cells_to_unlock = min(len(self.cells), atomic_number + 2)
        self.unlocked_cells = min(cells_to_unlock, len(self.cells))
        
        for i, cell in enumerate(self.cells):
            cell['locked'] = i >= self.unlocked_cells
            
            if not cell['locked']:
                electronegativity = current_element.get('electronegativity', 1.0) or 1.0
                cell['phase'] += 0.01 * electronegativity
                cell['energy'] = 0.8 + 0.5 * math.sin(time * 0.001 + cell['phase'])
                
                if (cell['x'] + cell['y'] + int(time * 0.01)) % atomic_number == 0:
                    self.active_cells.add((cell['x'], cell['y']))
                elif (cell['x'], cell['y']) in self.active_cells:
                    self.active_cells.remove((cell['x'], cell['y']))
    
    def draw(self, surface: pygame.Surface) -> None:
        grid_surface = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.SRCALPHA)
        
        for cell in self.cells:
            if cell['locked']:
                continue
                
            x = self.grid_offset[0] + cell['x'] * self.cell_size
            y = self.grid_offset[1] + cell['y'] * self.cell_size
            
            pulse = 0.8 + 0.2 * math.sin(self.pulse_phase + cell['phase'])
            r, g, b, a = cell['color']
            color = (
                min(255, int(r * pulse * cell['energy'])),
                min(255, int(g * pulse * cell['energy'])),
                min(255, int(b * pulse * cell['energy'])),
                a
            )
            
            cell_rect = pygame.Rect(x, y, self.cell_size, self.cell_size)
            pygame.draw.rect(grid_surface, color, cell_rect, 1, border_radius=5)
            
            if (cell['x'], cell['y']) in self.active_cells:
                highlight_color = (
                    min(255, color[0] + 100),
                    min(255, color[1] + 100),
                    min(255, color[2] + 100),
                    min(255, color[3] + 50)
                )
                pygame.draw.rect(grid_surface, highlight_color, 
                                cell_rect.inflate(-5, -5), 0, border_radius=3)
                
                for other_x, other_y in self.active_cells:
                    if (other_x, other_y) != (cell['x'], cell['y']):
                        ox = self.grid_offset[0] + other_x * self.cell_size + self.cell_size // 2
                        oy = self.grid_offset[1] + other_y * self.cell_size + self.cell_size // 2
                        cx = x + self.cell_size // 2
                        cy = y + self.cell_size // 2
                        
                        line_surface = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.SRCALPHA)
                        pygame.draw.line(line_surface, (*highlight_color[:3], 80), (cx, cy), (ox, oy), 2)
                        grid_surface.blit(line_surface, (0, 0))
            
            if (cell['x'], cell['y']) in self.active_cells:
                symbol = grid_font.render("Q", True, (255, 255, 255, 200))
                grid_surface.blit(symbol, (x + self.cell_size//2 - symbol.get_width()//2, 
                                    y + self.cell_size//2 - symbol.get_height()//2))
        
        grid_surface.set_alpha(self.grid_alpha)
        surface.blit(grid_surface, (0, 0))

# ======================
# Transition Effects
class TransitionEffects:
    def __init__(self):
        self.transition_time = 0
        self.max_transition_time = MAX_TRANSITION_TIME
        self.transition_active = False
        self.transition_type = "quantum"
        self.transition_surface = pygame.Surface((SCREEN_WIDTH, SCREEN_HEIGHT), pygame.SRCALPHA)
        self.transition_color = (150, 100, 255)
        
    def start_transition(self) -> None:
        self.transition_time = 0
        self.transition_active = True
        
    def update(self, dt: int) -> None:
        if self.transition_active:
            self.transition_time += dt
            if self.transition_time >= self.max_transition_time:
                self.transition_active = False
                
    def draw(self, surface: pygame.Surface) -> None:
        if not self.transition_active:
            return
            
        progress = self.transition_time / self.max_transition_time
        self.transition_surface.fill((0, 0, 0, 0))
        
        if progress < 0.5:
            alpha = int(255 * progress * 2)
            wave_height = int(SCREEN_HEIGHT * progress * 2)
            
            for x in range(0, SCREEN_WIDTH, 5):
                offset = math.sin(x / 50 + progress * 10) * 20
                pygame.draw.line(
                    self.transition_surface, 
                    (*self.transition_color[:3], alpha),
                    (x, SCREEN_HEIGHT - wave_height + offset),
                    (x, SCREEN_HEIGHT),
                    3
                )
        else:
            alpha = int(255 * (1 - progress) * 2)
            wave_height = int(SCREEN_HEIGHT * (1 - progress) * 2)
            
            for x in range(0, SCREEN_WIDTH, 5):
                offset = math.sin(x / 50 + progress * 10) * 20
                pygame.draw.line(
                    self.transition_surface, 
                    (*self.transition_color[:3], alpha),
                    (x, 0),
                    (x, wave_height + offset),
                    3
                )
        
        surface.blit(self.transition_surface, (0, 0))

# ======================
# Game State
class GameState:
    def __init__(self):
        self.selected_element_key = next(
            key for key, elem in periodic_table.items()
            if elem['atomic_number'] == 1
        )
        self.current_element = periodic_table[self.selected_element_key]
        self.animation_time = 0
        self.electron_positions: List[Tuple[float, float, int]] = []
        self.show_info = True
        self.show_orbitals = True
        self.show_electrons = True
        self.zoom = 1.0
        self.particles: List['Particle'] = []
        self.last_particle_time = 0
        self.camera_offset = [0, 0]
        self.dragging = False
        self.last_mouse_pos = (0, 0)
        self.background = DynamicBackground()
        self.quantum_grid = QuantumGrid()
        self.transition = TransitionEffects()
        self.element_progression = 0
        self.max_progression = max(e['atomic_number'] for e in periodic_table.values())

game_state = GameState()

# ======================
# Particle System
class Particle:
    def __init__(self, x: float, y: float, element: Dict):
        self.x = x
        self.y = y
        self.size = random.randint(2, 8)
        self.color = AGAPE_COLORS['Lithium']
        self.life = random.randint(50, 150)
        self.velocity = [random.uniform(-2, 2), random.uniform(-2, 2)]
        self.quantum_phase = random.uniform(0, 2 * math.pi)
        self.quantum_freq = random.uniform(0.01, 0.05)
        self.element = element
        
        if 'electronegativity' in element:
            en = element['electronegativity'] or 1.0
            self.color = (
                min(255, int(en * 80)),
                min(255, 50 + int((element['atomic_number'] % 10) * 20)),
                min(255, 100 + int((element['atomic_mass'] or 1) % 100))
            )
    
    def update(self) -> None:
        self.quantum_phase += self.quantum_freq
        quantum_effect = math.sin(self.quantum_phase) * 0.5 + 0.5
        
        self.x += self.velocity[0] * (0.5 + quantum_effect)
        self.y += self.velocity[1] * (0.5 + quantum_effect)
        
        if random.random() < 0.1:
            self.life -= random.randint(1, 3)
        
        self.size = max(1, self.size + math.sin(self.quantum_phase * 2) * 0.3)
    
    def draw(self, surface: pygame.Surface) -> None:
        alpha = min(255, self.life * 2)
        color = (*self.color[:3], int(alpha))
        
        s = pygame.Surface((self.size*4, self.size*4), pygame.SRCALPHA)
        pygame.draw.circle(s, (*color[:3], alpha//3), 
                         (self.size*2, self.size*2), self.size*2)
        pygame.draw.circle(s, color, 
                         (self.size*2, self.size*2), self.size)
        surface.blit(s, (int(self.x)-self.size*2, int(self.y)-self.size*2))

def update_particles() -> None:
    current_time = pygame.time.get_ticks()
    if current_time - game_state.last_particle_time > 50:
        center_x = SCREEN_WIDTH // 2 + game_state.camera_offset[0]
        center_y = SCREEN_HEIGHT // 2 + game_state.camera_offset[1]
        
        for _ in range(3):
            angle = random.uniform(0, 2 * math.pi)
            radius = random.randint(30, 100)
            x = center_x + math.cos(angle) * radius
            y = center_y + math.sin(angle) * radius
            game_state.particles.append(Particle(x, y, game_state.current_element))
        
        game_state.last_particle_time = current_time
    
    for particle in game_state.particles[:]:
        particle.update()
        if particle.life <= 0:
            game_state.particles.remove(particle)

# ======================
# Drawing Functions
def calculate_electron_positions(atomic_number: int, time: int) -> List[Tuple[float, float, int]]:
    positions = []
    shell_config = [2, 8, 8, 18, 18, 32]
    remaining_electrons = atomic_number
    
    grid_factor = len(game_state.quantum_grid.active_cells) / (game_state.quantum_grid.width * game_state.quantum_grid.height)
    shell_config = [max(1, int(s * (1 + grid_factor * 0.5))) for s in shell_config]
    
    for shell, max_electrons in enumerate(shell_config, 1):
        if remaining_electrons <= 0:
            break
        electrons_in_shell = min(max_electrons, remaining_electrons)
        remaining_electrons -= electrons_in_shell
        radius = 30 + shell * 25
        quantum_fluctuation = math.sin(time * 0.001 + shell) * 5 * grid_factor
        
        angle_step = (2 * math.pi) / max(1, electrons_in_shell)
        for i in range(electrons_in_shell):
            angle = angle_step * i + (time * 0.0005 * shell)
            x = math.cos(angle) * (radius + quantum_fluctuation)
            y = math.sin(angle) * (radius + quantum_fluctuation)
            positions.append((x, y, shell-1))
    return positions

def draw_element_visual(screen: pygame.Surface, element: Dict, center_x: int, center_y: int, time: int) -> None:
    atomic_number = element.get('atomic_number', 1)
    electronegativity = element.get('electronegativity', 1.0) or 1.0
    atomic_mass = element.get('atomic_mass', 1.0) or 1.0
    nucleus_radius = 10 + min(20, math.log(atomic_mass) * 2)
    red = int(min(255, electronegativity * 60))
    blue = 255 - red
    nucleus_color = (red, 50, blue)
    
    if game_state.show_orbitals:
        shell_config = [2, 8, 8, 18, 18, 32]
        for shell in range(1, len(shell_config)+1):
            radius = 30 + shell * 25
            s = pygame.Surface((radius*4, radius*4), pygame.SRCALPHA)
            glow_radius = radius + math.sin(time * 0.001 + shell) * 5
            pygame.draw.circle(s, (red, 50, blue, 30), 
                             (radius*2, radius*2), glow_radius, 3)
            pygame.draw.circle(s, (red, 50, blue, 80), 
                             (radius*2, radius*2), radius, 1)
            screen.blit(s, (center_x - radius*2, center_y - radius*2))
    
    pulse = 0.8 + 0.2 * math.sin(time * 0.005)
    pygame.draw.circle(screen, nucleus_color, 
                      (center_x, center_y), int(nucleus_radius * game_state.zoom * pulse))
    pygame.draw.circle(screen, AGAPE_COLORS['AGAPE'], 
                      (center_x, center_y), 8 * pulse)
    
    if len(game_state.quantum_grid.active_cells) > 3:
        wave_surface = pygame.Surface((nucleus_radius*8, nucleus_radius*8), pygame.SRCALPHA)
        for i in range(1, 4):
            wave_radius = nucleus_radius * 2 * i + math.sin(time * 0.002 + i) * 10
            alpha = 100 - i * 20
            pygame.draw.circle(wave_surface, (*nucleus_color[:3], alpha), 
                             (nucleus_radius*4, nucleus_radius*4), 
                             int(wave_radius), 2)
        screen.blit(wave_surface, (center_x - nucleus_radius*4, center_y - nucleus_radius*4))
    
    if game_state.show_electrons:
        positions = calculate_electron_positions(atomic_number, time)
        for x, y, shell in positions:
            color_idx = shell % len(ELECTRON_COLORS)
            color = ELECTRON_COLORS[color_idx]
            
            if (x, y) in [(p[0], p[1]) for p in positions[:atomic_number//2]]:
                pulse = 0.7 + 0.3 * math.sin(time * 0.01 + shell)
                size = 6 * pulse
            else:
                size = 4
            
            pygame.draw.circle(screen, color,
                             (center_x + int(x * game_state.zoom),
                             center_y + int(y * game_state.zoom)),
                             int(size))

def draw_element_info(screen: pygame.Surface, element: Dict, x: int, y: int) -> None:
    if not game_state.show_info:
        return
        
    info_panel = pygame.Surface((300, 200), pygame.SRCALPHA)
    info_panel.fill((20, 10, 40, 180))
    pygame.draw.rect(info_panel, (100, 80, 150, 150), info_panel.get_rect(), 1)
    screen.blit(info_panel, (x - 10, y - 10))
    
    symbol_text = element_font.render(element['symbol'], True, HIGHLIGHT)
    name_text = title_font.render(element['name'], True, TEXT_COLOR)
    number_text = info_font.render(f"Atomic Number: {element['atomic_number']}", True, TEXT_COLOR)
    mass_text = info_font.render(f"Atomic Mass: {element.get('atomic_mass', 'N/A')}", True, TEXT_COLOR)
    
    progress = game_state.element_progression / game_state.max_progression
    pygame.draw.rect(screen, (50, 50, 80), (x, y + 200, 300, 10))
    pygame.draw.rect(screen, (100, 150, 255), (x, y + 200, int(300 * progress), 10))
    
    screen.blit(symbol_text, (x, y))
    screen.blit(name_text, (x, y + 80))
    screen.blit(number_text, (x, y + 140))
    screen.blit(mass_text, (x, y + 170))

def draw_controls(screen: pygame.Surface) -> None:
    controls = [
        "Controls:",
        "Space/Right - Next Element",
        "Left - Previous Element",
        "I - Toggle Info",
        "O - Toggle Orbitals",
        "E - Toggle Electrons",
        "Mouse Wheel - Zoom",
        "Mouse Drag - Move View",
        "R - Reset View",
        "G - Toggle Quantum Grid"
    ]
    
    control_panel = pygame.Surface((250, 25 + len(controls) * 25), pygame.SRCALPHA)
    control_panel.fill((20, 10, 40, 180))
    pygame.draw.rect(control_panel, (100, 80, 150, 150), control_panel.get_rect(), 1)
    screen.blit(control_panel, (15, 15))
    
    for i, control in enumerate(controls):
        color = HIGHLIGHT if i == 0 else TEXT_COLOR
        text = small_font.render(control, True, color)
        screen.blit(text, (20, 20 + i * 25))

# ======================
# Sound Generation
def generate_tone(frequency: float, duration: float = 0.5, volume: float = 0.3, 
                 sample_rate: int = 44100, wave_type: str = 'sine') -> pygame.mixer.Sound:
    t = np.linspace(0, duration, int(sample_rate * duration), False)
    if wave_type == 'sine':
        tone = np.sin(frequency * t * 2 * np.pi)
    elif wave_type == 'square':
        tone = np.sign(np.sin(frequency * t * 2 * np.pi))
    elif wave_type == 'sawtooth':
        tone = 2 * (t * frequency - np.floor(0.5 + t * frequency))
    elif wave_type == 'triangle':
        tone = 2 * np.abs(2 * (t * frequency - np.floor(t * frequency + 0.5))) - 1
    
    envelope = np.ones_like(t)
    attack = int(0.05 * sample_rate)
    release = int(0.2 * sample_rate)
    if attack > 0:
        envelope[:attack] = np.linspace(0, 1, attack)
    if release > 0:
        envelope[-release:] = np.linspace(1, 0, release)
    
    tone = tone * envelope
    tone = (tone * (2**15 - 1) * volume).astype(np.int16)
    stereo_tone = np.column_stack((tone, tone))
    return pygame.sndarray.make_sound(stereo_tone)

def play_element_sound(element: Dict) -> None:
    gyromagnetic = abs(element['nmr_data'].get('gyromagnetic_ratio') or 10)
    base_freq = 220 + (element['atomic_number'] * 5)
    frequency = base_freq * (1 + gyromagnetic / 100)
    atomic_number = element['atomic_number']
    wave_type = (
        'sine' if atomic_number <= 2 else
        'triangle' if atomic_number <= 10 else
        'square' if atomic_number <= 18 else
        'sawtooth'
    )
    duration = 0.3 + (0.7 * (1 - (atomic_number % 10) / 10))
    tone = generate_tone(frequency, duration, 0.3, wave_type=wave_type)
    tone.play()

# ======================
# Main Game Loop
def main() -> None:
    running = True
    last_time = pygame.time.get_ticks()

    while running:
        current_time = pygame.time.get_ticks()
        dt = current_time - last_time
        last_time = current_time
        
        game_state.background.update()
        game_state.quantum_grid.update(game_state.current_element, current_time)
        game_state.transition.update(dt)
        update_particles()
        
        game_state.background.draw(screen)
        game_state.quantum_grid.draw(screen)
        
        center_x = screen.get_width() // 2 + game_state.camera_offset[0]
        center_y = screen.get_height() // 2 + game_state.camera_offset[1]
        
        for particle in game_state.particles:
            particle.draw(screen)
        
        draw_element_visual(screen, game_state.current_element, center_x, center_y, current_time)
        draw_element_info(screen, game_state.current_element, 50, 300)
        draw_controls(screen)
        
        status_text = small_font.render(
            f"Element {game_state.current_element['atomic_number']} of {len(periodic_table)} | "
            f"Quantum Grid: {len(game_state.quantum_grid.active_cells)}/{game_state.quantum_grid.unlocked_cells} active",
            True,
            TEXT_COLOR
        )
        screen.blit(status_text, (screen.get_width() - 400, 20))
        
        game_state.transition.draw(screen)
        
        for event in pygame.event.get():
            if event.type == pygame.QUIT:
                running = False
            elif event.type == pygame.KEYDOWN:
                if event.key in [pygame.K_SPACE, pygame.K_RIGHT]:
                    current_atomic_number = game_state.current_element['atomic_number']
                    available_atomic_numbers = sorted(
                        e['atomic_number'] for e in periodic_table.values()
                    )
                    current_index = available_atomic_numbers.index(current_atomic_number)
                    next_index = (current_index + 1) % len(available_atomic_numbers)
                    next_atomic_number = available_atomic_numbers[next_index]
                    
                    game_state.element_progression = max(game_state.element_progression, next_atomic_number)
                    
                    for key, elem in periodic_table.items():
                        if elem['atomic_number'] == next_atomic_number:
                            game_state.selected_element_key = key
                            game_state.current_element = elem
                            play_element_sound(game_state.current_element)
                            game_state.transition.start_transition()
                            break
                            
                elif event.key == pygame.K_LEFT:
                    current_atomic_number = game_state.current_element['atomic_number']
                    available_atomic_numbers = sorted(
                        e['atomic_number'] for e in periodic_table.values()
                    )
                    current_index = available_atomic_numbers.index(current_atomic_number)
                    prev_index = (current_index - 1) % len(available_atomic_numbers)
                    prev_atomic_number = available_atomic_numbers[prev_index]
                    
                    for key, elem in periodic_table.items():
                        if elem['atomic_number'] == prev_atomic_number:
                            game_state.selected_element_key = key
                            game_state.current_element = elem
                            play_element_sound(game_state.current_element)
                            game_state.transition.start_transition()
                            break
                            
                elif event.key == pygame.K_i:
                    game_state.show_info = not game_state.show_info
                elif event.key == pygame.K_o:
                    game_state.show_orbitals = not game_state.show_orbitals
                elif event.key == pygame.K_e:
                    game_state.show_electrons = not game_state.show_electrons
                elif event.key == pygame.K_r:
                    game_state.zoom = 1.0
                    game_state.camera_offset = [0, 0]
                elif event.key == pygame.K_g:
                    game_state.quantum_grid.grid_alpha = 0 if game_state.quantum_grid.grid_alpha > 0 else GRID_ALPHA
                    
            elif event.type == pygame.MOUSEBUTTONDOWN:
                if event.button == 1:
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
                zoom_factor = 1.1 if event.y > 0 else 0.9
                game_state.zoom = max(0.5, min(2.0, game_state.zoom * zoom_factor))

        pygame.display.flip()
        clock.tick(FPS)

    pygame.quit()
    sys.exit()

if __name__ == "__main__":
    main()
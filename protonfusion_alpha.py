#!/usr/bin/env python3
"""
Proton Fusion Drift - AGAPE Evolved Edition
Doctor Solen DriftCore
"""

import pygame
import numpy as np
import json
import math
import sys
import random

# ======================
# Load periodic table data with error handling
try:
    with open('periodic_table.json') as f:
        periodic_table = json.load(f)
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
screen = pygame.display.set_mode((1024, 768), pygame.RESIZABLE)
pygame.display.set_caption("Proton Fusion Drift - AGAPE Evolved Edition")
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
    sound = pygame.sndarray.make_sound(stereo_tone)
    return sound

def play_element_sound(element):
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
# Particle System (AGAPE with Uvite Birefringence)
class Particle:
    def __init__(self, x, y, element):
        self.x = x
        self.y = y
        self.size = random.randint(2, 5)
        self.color = AGAPE_COLORS['Lithium']
        self.life = 100
        self.velocity = [random.uniform(-1, 1), random.uniform(-1, 1)]
        self.birefringence_offset = random.uniform(-0.5, 0.5)
    def update(self):
        drift_speed = 1 + self.birefringence_offset
        self.x += self.velocity[0] * drift_speed
        self.y += self.velocity[1] * drift_speed
        self.life -= 1
        self.size = max(0, self.size - 0.05)
    def draw(self, surface):
        alpha = min(255, self.life * 2.55)
        color = (*self.color[:3], int(alpha))
        s = pygame.Surface((self.size*2, self.size*2), pygame.SRCALPHA)
        pygame.draw.circle(s, color, (self.size, self.size), self.size)
        surface.blit(s, (int(self.x)-self.size, int(self.y)-self.size))

def update_particles():
    current_time = pygame.time.get_ticks()
    if current_time - game_state.last_particle_time > 100:
        game_state.particles.append(Particle(
            random.randint(100, 924),
            random.randint(100, 668),
            game_state.current_element
        ))
        game_state.last_particle_time = current_time
    for particle in game_state.particles[:]:
        particle.update()
        if particle.life <= 0:
            game_state.particles.remove(particle)

# ======================
# Drawing Functions
def calculate_electron_positions(atomic_number, time):
    positions = []
    shell_config = [2, 8, 8, 18, 18, 32]
    remaining_electrons = atomic_number
    for shell, max_electrons in enumerate(shell_config, 1):
        if remaining_electrons <= 0:
            break
        electrons_in_shell = min(max_electrons, remaining_electrons)
        remaining_electrons -= electrons_in_shell
        radius = 30 + shell * 25
        angle_step = (2 * math.pi) / electrons_in_shell
        for i in range(electrons_in_shell):
            angle = angle_step * i + (time * 0.0005 * shell)
            x = math.cos(angle) * radius
            y = math.sin(angle) * radius
            positions.append((x, y, shell-1))
    return positions

def draw_element_visual(screen, element, center_x, center_y, time):
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
            s = pygame.Surface((radius*2, radius*2), pygame.SRCALPHA)
            pygame.draw.circle(s, (red, 50, blue, 60), (radius, radius), radius, 1)
            screen.blit(s, (center_x - radius, center_y - radius))
    pygame.draw.circle(screen, nucleus_color, (center_x, center_y), int(nucleus_radius * game_state.zoom))
    if game_state.show_electrons:
        positions = calculate_electron_positions(atomic_number, time)
        for x, y, shell in positions:
            color_idx = shell % len(ELECTRON_COLORS)
            color = ELECTRON_COLORS[color_idx]
            pygame.draw.circle(screen, color,
                               (center_x + int(x * game_state.zoom),
                                center_y + int(y * game_state.zoom)),
                               4)
    pygame.draw.circle(screen, AGAPE_COLORS['AGAPE'], (center_x, center_y), 8)

# ======================
# Info Display
def draw_element_info(screen, element, x, y):
    if not game_state.show_info:
        return
    symbol_text = element_font.render(element['symbol'], True, HIGHLIGHT)
    name_text = title_font.render(element['name'], True, TEXT_COLOR)
    number_text = info_font.render(f"Atomic Number: {element['atomic_number']}", True, TEXT_COLOR)
    mass_text = info_font.render(f"Atomic Mass: {element.get('atomic_mass', 'N/A')}", True, TEXT_COLOR)
    screen.blit(symbol_text, (x, y))
    screen.blit(name_text, (x, y + 80))
    screen.blit(number_text, (x, y + 140))
    screen.blit(mass_text, (x, y + 170))

# ======================
# Controls Display
def draw_controls(screen):
    controls = [
        "Controls:",
        "Space/Right - Next Element",
        "Left - Previous Element",
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
# Main Game Loop
running = True
while running:
    current_time = pygame.time.get_ticks()
    screen.fill(BACKGROUND)
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
                for key, elem in periodic_table.items():
                    if elem['atomic_number'] == next_atomic_number:
                        game_state.selected_element_key = key
                        game_state.current_element = elem
                        play_element_sound(game_state.current_element)
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

    update_particles()
    center_x = screen.get_width() // 2 + game_state.camera_offset[0]
    center_y = screen.get_height() // 2 + game_state.camera_offset[1]
    for particle in game_state.particles:
        particle.draw(screen)
    draw_element_visual(screen, game_state.current_element, center_x, center_y, current_time)
    draw_element_info(screen, game_state.current_element, 50, 300)
    draw_controls(screen)
    status_text = small_font.render(
        f"Element {game_state.current_element['atomic_number']} of {len(periodic_table)}",
        True,
        TEXT_COLOR
    )
    screen.blit(status_text, (screen.get_width() - 250, 20))
    pygame.display.flip()
    clock.tick(60)

pygame.quit()
sys.exit()

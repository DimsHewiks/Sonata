<?php

namespace View\Home\Pages;

class HomePage
{
    public function home():string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sonata FW</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #fff5e6 0%, #ffeed9 50%, #ffe8cc 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #333;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 60px 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 
                0 10px 40px rgba(255, 140, 0, 0.08),
                0 2px 12px rgba(0, 0, 0, 0.02),
                inset 0 0 0 1px rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            text-align: center;
            position: relative;
            overflow: hidden;
            transform: translateY(30px);
            opacity: 0;
            animation: cardAppear 0.8s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
        }
        
        @keyframes cardAppear {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .logo {
            margin-bottom: 40px;
            position: relative;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.2);
        }
        
        .logo-icon i {
            color: white;
            font-size: 32px;
        }
        
        .logo-text {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }
        
        .subtitle {
            font-size: 18px;
            color: #ff8c00;
            font-weight: 500;
            margin-bottom: 8px;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            line-height: 1.3;
            color: #222;
        }
        
        .highlight {
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }
        
        .accent {
            color: #00bfff;
            font-weight: 600;
        }
        
        .message {
            font-size: 18px;
            line-height: 1.7;
            color: #555;
            margin-bottom: 40px;
            font-weight: 400;
        }
        
        .fox-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin: 0 auto 40px;
            overflow: hidden;
            position: relative;
            border: 4px solid white;
            box-shadow: 
                0 10px 30px rgba(255, 107, 0, 0.15),
                0 0 0 1px rgba(255, 140, 0, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .fox-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(1.1) contrast(1.05);
        }
        
        .kitsune-text {
            font-size: 14px;
            color: #ff8c00;
            font-weight: 500;
            margin-top: 8px;
            letter-spacing: 1px;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 140, 0, 0.2), transparent);
            margin: 35px 0;
        }
        
        .start-button {
            display: inline-block;
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.25);
            position: relative;
            overflow: hidden;
        }
        
        .start-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 0, 0.3);
        }
        
        .start-button:active {
            transform: translateY(-1px);
        }
        
        .start-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
        }
        
        .start-button:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }
        
        .decoration {
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            opacity: 0.4;
            filter: blur(20px);
        }
        
        .decoration-1 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #ff8c00, transparent);
            top: -80px;
            right: -80px;
        }
        
        .decoration-2 {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #00bfff, transparent);
            bottom: -60px;
            left: -60px;
        }
        
        @media (max-width: 600px) {
            .card {
                padding: 40px 30px;
            }
            
            .title {
                font-size: 28px;
            }
            
            .message {
                font-size: 16px;
            }
            
            .fox-image {
                width: 150px;
                height: 150px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="card">
        <div class="decoration decoration-1"></div>
        <div class="decoration decoration-2"></div>
        
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-paw"></i>
            </div>
            <div class="logo-text">Sonata FW</div>
            <div class="subtitle">PHP Framework</div>
        </div>
        
        <div class="fox-image">
            <img src="https://images.unsplash.com/photo-1564349683136-77e08dba1ef7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" alt="Kitsune - Японская лиса">
            <div class="kitsune-text">KITSUNE</div>
        </div>
        
        <div class="title">
            Входи в мир <span class="highlight">элегантного кода</span>
        </div>
        
        <p class="message">
            <span class="accent">Sonata FW</span> — фреймворк, вдохновлённый мудростью и грацией японской лисы. 
            Минимализм, скорость и мощь в каждом компоненте.
        </p>
        
        <div class="divider"></div>
        
        <button class="start-button">
            <i class="fas fa-play" style="margin-right: 10px;"></i>
            Начать создавать
        </button>
    </div>

    <script>
        // Добавляем небольшую задержку для появления декоративных элементов
        document.addEventListener('DOMContentLoaded', function() {
            const decorations = document.querySelectorAll('.decoration');
            
            setTimeout(() => {
                decorations.forEach(decoration => {
                    decoration.style.opacity = '0.4';
                });
            }, 500);
            
            // Изначально скрываем декоративные элементы
            decorations.forEach(decoration => {
                decoration.style.opacity = '0';
                decoration.style.transition = 'opacity 1.5s ease';
            });
            
            // Анимация для кнопки
            const button = document.querySelector('.start-button');
            button.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Простое сообщение при клике
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 10px;"></i> Запуск...';
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    alert('Sonata FW инициализирован! Начинаем разработку.');
                }, 800);
            });
        });
    </script>
</body>
</html>
HTML;
    }
}
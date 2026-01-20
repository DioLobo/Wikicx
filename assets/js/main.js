        function toggleTheme() {
            const html = document.documentElement;
            const isLight = html.classList.toggle('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            const icon = document.querySelector('.theme-switch i');
            if (isLight) { icon.classList.replace('fa-sun', 'fa-moon'); } else { icon.classList.replace('fa-moon', 'fa-sun'); }
        }
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebar-overlay').classList.toggle('active');
        }
        let questions = []; let currentQ = 0; let score = 0; let selected = -1; let userAnswers = [];
        function switchView(viewName, clickedElement, module = null) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.getElementById('view-' + viewName).classList.add('active');
            if(clickedElement) {
                document.querySelectorAll('.submenu a').forEach(el => el.classList.remove('active-link'));
                clickedElement.classList.add('active-link');
            }
            if(viewName === 'quiz') startQuiz(module);
            if(window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebar-overlay').classList.remove('active');
            }
        }
        function startQuiz(moduleName) {
            document.getElementById('quiz-playable').style.display = 'block';
            document.getElementById('quiz-gabarito').style.display = 'none';
            document.getElementById('q-text').innerText = "Carregando perguntas...";
            document.getElementById('q-options').innerHTML = "";
            document.getElementById('quiz-progress').style.width = '0%';
            userAnswers = [];
            let url = 'get_quiz.php' + (moduleName ? '?module=' + encodeURIComponent(moduleName) : '');
            fetch(url).then(response => response.json()).then(data => {
                if (data.length === 0) { alert("Sem perguntas cadastradas."); switchView('dashboard', null); return; }
                questions = data; currentQ = 0; score = 0; renderQuestion();
            });
        }
        function renderQuestion() {
            const q = questions[currentQ];
            document.getElementById('q-number').innerText = currentQ + 1;
            document.getElementById('quiz-progress').style.width = ((currentQ + 1) / questions.length * 100) + '%';
            document.getElementById('q-text').innerText = q.q;
            document.getElementById('quiz-module-tag').innerText = q.module;
            let htmlOpts = '';
            q.opts.forEach((opt, i) => { htmlOpts += `<button class="option-btn" onclick="selectOpt(${i}, this)">${opt}</button>`; });
            document.getElementById('q-options').innerHTML = htmlOpts;
            document.getElementById('btn-next-q').disabled = true;
        }
        function selectOpt(index, btn) {
            selected = index;
            document.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            document.getElementById('btn-next-q').disabled = false;
        }
        function nextQuestion() {
            userAnswers.push(selected);
            if (selected === questions[currentQ].ans) score++;
            currentQ++;
            if (currentQ < questions.length) renderQuestion(); else showResultModal();
        }
        function showResultModal() {
            document.getElementById('final-score').innerText = `${score}/${questions.length}`;
            document.getElementById('quiz-result-modal').style.display = 'flex';
        }
        function finishAndSaveQuiz() {
            fetch('save_quiz.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ score: score }) })
            .then(res => res.json()).then(() => { 
                document.getElementById('quiz-result-modal').style.display = 'none'; 
                renderGabarito(score); 
            });
        }

        // FUNÇÃO GABARITO COMPLETA E RESTAURADA
        function renderGabarito(finalScore) {
            document.getElementById('quiz-playable').style.display = 'none';
            const gabaritoDiv = document.getElementById('quiz-gabarito');
            gabaritoDiv.style.display = 'block';
            let html = `<h2 style='text-align:center; color:var(--accent); margin-bottom:20px;'>Resultado: ${finalScore}/${questions.length}</h2>`;
            questions.forEach((q, index) => {
                const userAnswer = userAnswers[index];
                html += `<div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border);">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: 15px; margin-bottom:15px;">
                                <p style="font-weight:bold; font-size: 1.1rem; margin:0; flex:1;">${index + 1}. ${q.q}</p>
                                <span style="font-size:0.7rem; color:var(--accent); border:1px solid var(--accent); padding:2px 8px; border-radius:4px;">${q.module}</span>
                            </div>`;
                q.opts.forEach((opt, i) => {
                    const isCorrect = i === q.ans;
                    const isSelected = i === userAnswer;
                    let styleClass = isCorrect ? 'gabarito-correct' : (isSelected ? 'gabarito-wrong' : 'gabarito-muted');
                    let icon = isCorrect ? '<i class="fas fa-check" style="float:right;"></i>' : (isSelected ? '<i class="fas fa-times" style="float:right;"></i>' : '');
                    html += `<div class="gabarito-option ${styleClass}">${opt} ${icon}</div>`;
                });
                html += `</div>`;
            });
            html += `<div style="display:flex; justify-content:flex-end; margin-top:20px;">
                        <button class="btn-outline-neon" onclick="switchView('dashboard', null)">
                            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                        </button>
                     </div>`;
            gabaritoDiv.innerHTML = html;
        }

        document.querySelectorAll('.folder-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const sub = this.nextElementSibling;
                const isOpen = sub.style.maxHeight;
                document.querySelectorAll('.submenu').forEach(s => { s.style.maxHeight = null; s.previousElementSibling.classList.remove('active-folder'); });
                if (!isOpen) { sub.style.maxHeight = sub.scrollHeight + "px"; this.classList.add('active-folder'); }
            });
        });

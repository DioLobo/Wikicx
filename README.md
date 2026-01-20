# 📚 CXPRO - Portal de Treinamento & Wiki

<div align="center">
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/html5/html5-original.svg" alt="html5" width="50" height="50"/>
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/css3/css3-original.svg" alt="css3" width="50" height="50"/>
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/javascript/javascript-original.svg" alt="javascript" width="50" height="50"/>
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg" alt="php" width="50" height="50"/>
    <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/mysql/mysql-original-wordmark.svg" alt="mysql" width="50" height="50"/>
</div>

<br>

Este é um Sistema de Gerenciamento de Aprendizado (LMS) robusto e moderno, projetado para oferecer uma experiência de ensino gamificada, segura e com controle administrativo total.

---

### 💻 Habilidades Técnicas Aplicadas
* **Linguagens:** HTML5, CSS3, JavaScript e PHP 8.x (PDO).
* **Banco de Dados:** MySQL (Modelagem relacional e integridade de dados).
* **Ferramentas:** Git, GitHub, VS Code e PHPMailer.

---

## 🖼️ Demonstração Visual

### 🎨 Interface e Experiência (UX)
| **Login & Temas** | **Mobile First** |
|:---:|:---:|
| <img src="screenshots/login tema.gif" width="400px"> | <img src="screenshots/mobile.gif" width="220px"> |
| *Suporte a Dark/Light Mode com memória de preferência.* | *Interface 100% responsiva para smartphones.* |

### 🎮 Gamificação e Progresso
| **Ganho de XP** | **Ranking Global** |
|:---:|:---:|
| <img src="screenshots/conclusao de aula + xp.gif" width="400px"> | <img src="screenshots/ranking.gif" width="400px"> |
| *Evolução de nível e XP ao finalizar aulas.* | *Mural competitivo entre os alunos da plataforma.* |

| **Certificação** | **Sistema de Quizzes** |
|:---:|:---:|
| <img src="screenshots/certificado.gif" width="400px"> | <img src="screenshots/quiz.gif" width="400px"> |
| *Liberação do certificado após 100% de conclusão.* | *Testes dinâmicos com feedback de gabarito inteligente.* |

### 🛡️ Segurança e Administração
| **Vigilante de Sessão** | **Painel Administrativo** |
|:---:|:---:|
| <img src="screenshots/proteção na sessao.gif" width="400px"> | <img src="screenshots/painel adm.gif" width="400px"> |
| *Anti-compartilhamento de conta em tempo real.* | *Gestão centralizada de módulos, aulas e usuários.* |

| **Gestão de Usuários** | **Downloads & Materiais** |
|:---:|:---:|
| <img src="screenshots/aprovando usuario.gif" width="400px"> | <img src="screenshots/banco de provas.gif" width="400px"> |
| *Aprovação manual de novos cadastros pelo ADM.* | *Central organizada para download de PDFs e provas.* |

| **Cadastro de Aluno** | **Recuperação de Senha** |
|:---:|:---:|
| <img src="screenshots/criando conta.gif" width="400px"> | <img src="screenshots/redefinição de senha.gif" width="400px"> |
| *Fluxo de registro intuitivo para novos alunos.* | *Reset seguro via e-mail com tokens temporários.* |

| **Notificação por E-mail** | **Edição de Perfil** |
|:---:|:---:|
| <img src="screenshots/aprovado.PNG" width="400px"> | <img src="screenshots/editar profile.gif" width="400px"> |
| *Design Neon Dark para e-mails de aprovação.* | *Gestão de avatar e dados pessoais pelo aluno.* |

---

## 🛠️ Instalação e Configuração Local

1.  **Clone o repositório:** `git clone https://github.com/DioLobo/Wikicx.git`
2.  **Servidor Local:** Mova a pasta para o `htdocs` (XAMPP) ou `www` (Wamp).
3.  **Banco de Dados:** Importe o arquivo `seu banco.sql` através do PHPMyAdmin.
4.  **Configuração:** Renomeie o arquivo `.env.example` para `.env` e insira suas credenciais do banco.

---
Para avaliar as funcionalidades administrativas e de aluno, utilize as credenciais abaixo integradas ao arquivo `seu banco.sql`:

| Nível de Acesso | Usuário (E-mail) | Senha | Status |
| :--- | :--- | :--- | :--- |
| ![Admin](https://img.shields.io/badge/ADMIN-red?style=flat-square) | `teste@gmail.com` | `password` | ![Ativo](https://img.shields.io/badge/Acesso_Liberado-brightgreen?style=flat-square) |

> **Nota:** A conta de administrador possui permissão para aprovar novos cadastros e gerenciar o conteúdo no diretório `/admin`.

---

## 📂 Organização do Projeto

A estrutura de pastas reflete a modularização do sistema:


* **/api**: Endpoints para comunicação assíncrona e lógica de quizzes.
* **/assets**: Recursos estáticos (Imagens, ícones, CSS global).
* **/config**: Parâmetros de conexão e vigilante de sessão.
* **/mailer**: Configuração do PHPMailer para disparos automáticos.
* **/screenshots**: Documentação visual do projeto.
* **/uploads**: Repositório de mídias e materiais de aula.
---



## 🤝 Contato

[![LinkedIn](https://img.shields.io/badge/LinkedIn-0077B5?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/diogomlobo/)
[![WhatsApp](https://img.shields.io/badge/WhatsApp-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)](https://wa.me/5521973073162)
[![E-mail](https://img.shields.io/badge/Email-D14836?style=for-the-badge&logo=gmail&logoColor=white)](mailto:diogo.dmlrj@gmail.com)
---

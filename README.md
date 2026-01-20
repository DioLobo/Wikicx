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
| *Liberação do certificado após 100% de conclusão.* | *Testes dinâmicos com feedback de gabarito.* |

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

| **Aprovação do aluno no email** | **Banco de provas** |
|:---:|:---:|
| <img src="screenshots/aprovado.PNG" width="400px"> | <img src="screenshots/redefinição de senha.gif" width="400px"> |
| *Aluno recebe um email de aprovação.* | *Um lugar para baixar documentos pdf, provas e gabarito.* |


---

## 🚀 Como Testar o Projeto

Para rodar o projeto localmente, siga os passos abaixo:

1. Importe o arquivo `seu banco.sql` no seu servidor MySQL.
2. Configure as credenciais de acesso no arquivo `.env`.
3. Utilize as credenciais de teste abaixo:

| **Usuário** | **Senha** | **Nível** |
|:---:|:---:|:---:|
| `teste@gmail.com` | `password` | Aluno/Teste |

---

## 📂 Organização do Projeto

A estrutura de pastas do projeto está organizada da seguinte forma:

* **/admin**: Telas de gestão de aulas, módulos e usuários.
* **/api**: Endpoints de verificação e lógica do sistema.
* **/assets**: Arquivos de recursos estáticos.
* **/config**: Conexão com DB e vigilante de sessão.
* **/mailer**: Lógica de envio de e-mails.
* **/screenshots**: Assets visuais da documentação.
* **/uploads**: Armazenamento de avatares e materiais.

---
*Desenvolvido por [DioLobo](https://github.com/DioLobo)*

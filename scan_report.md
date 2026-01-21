# Relatório de Análise do Plugin: Custom Post Visual Theme

**Versão:** 1.0.5
**Autor:** Marcos Cordeiro

## 1. Visão Geral

O plugin **Custom Post Visual Theme** permite aplicar um tema visual personalizado em posts individuais ou em grupos de posts. O "tema visual" consiste em até 4 imagens de fundo (uma para cada canto da tela) que são aplicadas à área de conteúdo principal do post.

O plugin oferece duas formas de aplicar um tema:

1.  **Em Massa:** Através de uma lista de IDs de posts definida em "presets" na página de configurações.
2.  **Individualmente:** Através de uma caixa de seleção na tela de edição de cada post, que permite escolher um "preset" para aquele conteúdo específico.

## 2. Estrutura de Arquivos

```
custom-post-visual-theme/
├── admin/
│   └── settings-page.php   # Lógica da página de configurações e meta box.
├── assets/
│   └── js/
│       └── admin.js        # JavaScript para interatividade da pág. de configurações.
├── frontend/
│   └── apply-theme.php     # Lógica para aplicar o tema no frontend.
└── custom-post-visual-theme.php  # Arquivo principal, inicializador do plugin.
```

## 3. Funcionamento Detalhado

### 3.1. Painel de Administração

O plugin cria uma página de configurações em **Configurações > Post Visual Theme**. Esta página é dividida em duas seções:

#### Configurações Avançadas
*   **Seletor de CSS Alvo:** Um campo de texto que permite ao administrador especificar o seletor de CSS exato (ex: `.minha-classe`, `#meu-id`) onde o tema visual será aplicado. Se este campo for preenchido, o plugin aplicará os estilos diretamente a este seletor. Se for deixado em branco, o plugin usará sua lógica de fallback para encontrar um alvo automaticamente.

#### Presets de Tema
Nesta seção, o administrador pode criar **"Presets de Tema"**.

Cada preset contém as seguintes configurações:
*   **Rótulo:** Um nome para identificar o preset (ex: "Campanha de Natal").
*   **IDs dos Posts:** Uma lista de IDs de posts (separados por vírgula) onde o tema será aplicado.
*   **Imagens de Fundo:** Campos para selecionar, via biblioteca de mídia do WordPress, uma imagem para cada um dos quatro cantos:
    *   Superior Esquerda
    *   Superior Direita
    *   Inferior Esquerda
    *   Inferior Direita
*   **Posicionamento Vertical:** Campos de texto para ajustar a posição vertical das imagens superiores e inferiores (ex: `100px`, `20%`).

Além disso, o plugin adiciona uma caixa de seleção ("meta box") na tela de edição de todos os tipos de posts públicos (`post`, `noticia`, etc.). Esta caixa, chamada **"CPVT Theme Preset"**, permite que o editor do post associe um dos presets criados diretamente àquele post. **Esta seleção individual tem prioridade sobre a configuração de IDs em massa.**

Os dados dos presets são salvos na tabela `wp_options` sob a chave `cpvt_themes`. A escolha individual por post é salva na tabela `wp_postmeta` com a chave `cpvt_theme`.

### 3.2. Lógica do Frontend

Quando uma página de post é carregada no site, o plugin executa os seguintes passos:

1.  **Verificação:** Ele checa se a página é um post singular (`is_singular`).
2.  **Determinação do Tema:** O sistema verifica se um preset foi definido para o post atual (seja por meta do post ou por ID global).
3.  **Determinação do Alvo:** O plugin verifica se um **Seletor de CSS Alvo** foi definido nas configurações avançadas.
4.  **Geração de Estilo (CSS):** Se um preset ativo for encontrado, o plugin gera dinamicamente regras de CSS.
    *   Se um seletor customizado foi definido, o CSS gerado terá aquele seletor como alvo.
    *   Se o seletor customizado estiver em branco, o CSS gerado terá como alvo a classe `.cpvt-theme-target`.
5.  **Seleção Automática de Alvo (Fallback):** Apenas se nenhum seletor customizado for especificado, um pequeno script é injetado no cabeçalho. Este script procura por seletores de conteúdo comuns (como `.site-main`, `.entry-content`, etc.) e adiciona a classe `.cpvt-theme-target` ao primeiro que encontrar.
6.  **Injeção do CSS:** O CSS gerado é então adicionado ao `head` da página, aplicando o estilo ao alvo determinado.

## 4. Scripts e Estilos

*   **`assets/js/admin.js`**: Carregado apenas na página de configurações do plugin. Usa jQuery para habilitar a adição e remoção dinâmica de presets e para abrir o seletor de mídia do WordPress.
*   **CSS Dinâmico (inline):** Gerado e aplicado no frontend apenas nas páginas que possuem um tema ativo.

## 5. Conclusão

O plugin é uma ferramenta focada e bem estruturada para "decorar" posts específicos com imagens de fundo. Ele separa de forma eficaz a lógica de administração e a lógica de exibição, utilizando as APIs do WordPress (`add_options_page`, `register_setting`, `add_meta_box`, `wp_enqueue_scripts`) de maneira correta. A abordagem de usar um seletor de classe genérico via JavaScript (`cpvt-theme-target`) para aplicar o estilo o torna compatível com a maioria dos temas e page builders.
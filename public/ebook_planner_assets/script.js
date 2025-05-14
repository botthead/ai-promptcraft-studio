import { GoogleGenerativeAI, HarmCategory, HarmBlockThreshold } from "https://esm.run/@google/generative-ai";

    const STORAGE_KEY = 'ebookPlannerState_v2_3';
    const AUTO_SAVE_INTERVAL = 5000;

    const ckEditorFieldIds = [
        'step1_q0', // Persona
        'step4_q1', // Detalhe os sub-tópicos de cada capítulo principal.
        'step8_q1', // Descreva o estilo visual e a formatação desejada.
    ];
    let ckEditorInstances = {};

    const steps = [
        // ... (ALL STEPS DEFINITIONS AS PROVIDED PREVIOUSLY, UNCHANGED) ...
        // --- Step 1: Ideia Central & Propósito ---
        {
            title: "1. Ideia Central & Propósito",
            questions: [
                { id: "step0_q0", type: "text", label: "Qual é o tema principal do eBook?", description: "Defina o assunto central de forma clara e concisa.", placeholder: "Ex: Marketing de Conteúdo para Pequenas Empresas", tooltip: "Seja específico. Sobre o que exatamente é o livro?", required: true },
                { id: "step0_q1", type: "textarea", rows: 3, label: "Qual problema específico este eBook resolve?", description: "Todo bom eBook soluciona uma dor ou atende a um desejo do leitor.", placeholder: "Ex: Ajuda empreendedores a criar um calendário editorial eficaz sem gastar muito.", tooltip: "Pense no benefício direto e tangível para o leitor.", required: true, aiSuggestion: { type: 'problem', buttonText: 'Sugerir Problemas', countDefault: 3} },
                { id: "step0_q2", type: "textarea", rows: 3, label: "Por que você (ou sua marca) quer escrevê-lo?", description: "Sua motivação pessoal ou empresarial conecta com o leitor e define o tom.", placeholder: "Ex: Compartilhar nossa expertise em SEO e gerar leads qualificados; Paixão por ensinar culinária vegana.", tooltip: "Qual sua paixão, experiência ou objetivo comercial com este eBook?" }
            ]
        },
        // --- Step 2: Público-Alvo Detalhado ---
        {
            title: "2. Público-Alvo Detalhado",
            questions: [
                { id: "step1_q0", type: "textarea", rows: 4, label: "Quem é seu leitor ideal (persona)? (Use o editor abaixo)", description: "Descreva detalhadamente: idade, profissão, desafios, objetivos, onde busca informação.", placeholder: "Ex: Maria, 35 anos, dona de loja de artesanato...", tooltip: "Quanto mais detalhada a persona, mais direcionado será o conteúdo.", required: true, aiSuggestion: { type: 'persona', buttonText: 'Elaborar Persona'} },
                {
                    id: "step1_q1", type: "radio", label: "Qual o nível de conhecimento do leitor sobre o tema?", description: "Isso define a profundidade, a linguagem e os pré-requisitos.", tooltip: "Ajuste o vocabulário e a complexidade ao nível do seu público.", required: true,
                    options: [
                        { value: "iniciante", label: "Iniciante (precisa de conceitos básicos)" }, { value: "intermediario", label: "Intermediário (conhece o básico, busca táticas)" }, { value: "avancado", label: "Avançado (busca estratégias aprofundadas)" }, { value: "misto", label: "Misto (abrange vários níveis)" }
                    ]
                },
                { id: "step1_q2", type: "textarea", rows: 3, label: "Qual a principal dor ou necessidade que o eBook vai sanar?", description: "Conecte o problema central (Passo 1) diretamente com a realidade do público.", placeholder: "Ex: Perda de tempo com tarefas manuais...", tooltip: "O que realmente incomoda ou o que seu leitor mais deseja alcançar?", required: true, aiSuggestion: { type: 'painPoint', buttonText: 'Sugerir Dores', countDefault: 3} }
            ]
        },
        // --- Step 3: Objetivo Claro do Livro ---
        {
            title: "3. Objetivo Claro do Livro",
            questions: [
                { id: "step2_q0", type: "textarea", rows: 3, label: "O que o leitor será capaz de fazer ou saber após a leitura?", description: "Defina a transformação ou o resultado prático esperado.", placeholder: "Ex: Criar sua primeira campanha de anúncios...", tooltip: "Seja específico sobre a habilidade ou conhecimento adquirido.", required: true, aiSuggestion: { type: 'outcome', buttonText: 'Sugerir Resultados', countDefault: 3} },
                {
                    id: "step2_q1", type: "radio", label: "Qual é o seu objetivo principal com este eBook?", description: "Selecione a intenção primária.", tooltip: "Alinhe o conteúdo do eBook com seu objetivo estratégico.", required: true,
                    options: [
                         { value: "educar", label: "Educar o mercado / Compartilhar conhecimento" }, { value: "gerar_leads", label: "Gerar Leads (capturar contatos)" }, { value: "vender_produto", label: "Apoiar a venda de um produto/serviço" }, { value: "construir_autoridade", label: "Construir autoridade no nicho" }, { value: "inspirar", label: "Inspirar ou motivar" },
                    ],
                    otherOption: true
                }
            ]
        },
        // --- Step 4: Título e Subtítulo ---
         {
            title: "4. Título e Subtítulo Magnéticos",
            questions: [
                { id: "step3_q0", type: "text", label: "Qual será o título principal?", description: "Deve ser claro, atraente e indicar o benefício principal.", placeholder: "Ex: Descomplique suas Finanças Pessoais", tooltip: "Use palavras-chave relevantes e foque no resultado ou na curiosidade.", required: true, aiSuggestion: { type: 'titles', buttonText: 'Sugerir Títulos', countDefault: 3} },
                { id: "step3_q1", type: "text", label: "Qual o subtítulo descritivo?", description: "Complementa o título, especificando o conteúdo ou o público.", placeholder: "Ex: Um Guia Prático para Organizar seu Orçamento...", tooltip: "Detalhe o que o leitor encontrará ou para quem o livro é destinado.", required: false },
                { id: "step3_q2", type: "textarea", rows: 3, label: "Resuma a proposta do eBook em uma única frase (Elevator Pitch).", description: "Uma frase curta e impactante que comunica o valor essencial.", placeholder: "Ex: Este eBook ensina freelancers a dobrarem sua renda...", tooltip: "Pense em como você apresentaria o livro rapidamente.", required: true, aiSuggestion: { type: 'elevatorPitch', buttonText: 'Sugerir Pitch', countDefault: 3} }
            ]
        },
        // --- Step 5: Estrutura e Índice ---
        {
            title: "5. Estrutura e Índice Detalhado",
            questions: [
                { id: "step4_q0", type: "textarea", rows: 5, label: "Quais serão os principais capítulos ou seções?", description: "Liste os grandes blocos de conteúdo. Use uma linha por capítulo.", placeholder: "1. Introdução à IA Generativa\n2. Principais Ferramentas...", tooltip: "Pense na progressão lógica do aprendizado do leitor.", required: true, aiSuggestion: { type: 'chapters', buttonText: 'Sugerir Capítulos', countDefault: 5} },
                { id: "step4_q1", type: "textarea", rows: 8, label: "Detalhe os sub-tópicos de cada capítulo principal. (Use o editor abaixo)", description: "Divida cada capítulo em pontos menores e específicos. Use indentação ou numeração (a IA pode ajudar aqui se você colar a lista de capítulos).", placeholder: "Cap 2: Ferramentas\n  2.1 ChatGPT\n  2.2 Midjourney...", tooltip: "Isso formará o seu índice e guiará a escrita.", required: true },
                {
                    id: "step4_q2", type: "checkbox", label: "Quais elementos adicionais o eBook terá?", description: "Marque todas as opções aplicáveis.", tooltip: "Estruture o livro completo, do início ao fim.",
                    options: [
                        { value: "introducao", label: "Introdução Detalhada" }, { value: "conclusao", label: "Conclusão / Resumo Final" }, { value: "sobre_autor", label: "Sobre o Autor" }, { value: "glossario", label: "Glossário de Termos" }, { value: "recursos", label: "Lista de Recursos / Links Úteis" }, { value: "apendices", label: "Apêndices (material extra)" }, { value: "cta", label: "Chamada para Ação (CTA) específica" },
                    ],
                    otherOption: true,
                    aiSuggestion: { type: 'extraElements', buttonText: 'Sugerir Elementos', countDefault: 3 }
                }
            ]
        },
        // --- Step 6: Pesquisa ---
        {
            title: "6. Pesquisa e Fontes de Conteúdo",
            questions: [
                { id: "step5_q0", type: "textarea", rows: 4, label: "Quais fontes de informação você utilizará?", description: "Liste livros, artigos, estudos, entrevistas, sua própria experiência, etc.", placeholder: "Ex: Artigos científicos recentes do PubMed...", tooltip: "Garanta a credibilidade e profundidade do seu conteúdo.", required: true },
                {
                    id: "step5_q1", type: "radio", label: "Será necessário citar fontes específicas (autores, dados, pesquisas)?", description: "Planeje como fará as referências para evitar plágio e dar crédito.", tooltip: "Defina um padrão de citação, se necessário.", required: true,
                    options: [
                        { value: "nao", label: "Não, o conteúdo é majoritariamente baseado em experiência própria ou conhecimento geral." }, { value: "sim_informal", label: "Sim, mas de forma informal (ex: 'Segundo autor X...')" }, { value: "sim_formal", label: "Sim, com citações formais (notas de rodapé, bibliografia, etc.)" },
                    ]
                },
                { id: "step5_q2", type: "textarea", rows: 2, label: "Se sim, qual será o método de citação?", description: "(Opcional) Descreva brevemente o método se escolheu uma opção 'Sim' acima.", placeholder: "Ex: Usarei notas de rodapé estilo ABNT...", tooltip: "Seja consistente no método escolhido." },
            ]
        },
        // --- Step 7: Tom de Voz ---
        {
            title: "7. Tom de Voz e Estilo de Redação",
            questions: [
                {
                    id: "step6_q0", type: "select", label: "Qual será o tom de voz predominante?", description: "Selecione o tom que melhor se conecta com seu público e objetivo.", tooltip: "O tom deve ser consistente ao longo do eBook.", required: true,
                    options: [
                        { value: "", label: "-- Selecione um Tom --" }, { value: "formal", label: "Formal / Acadêmico" }, { value: "profissional", label: "Profissional / Corporativo" }, { value: "informal", label: "Informal / Conversacional" }, { value: "didatico", label: "Didático / Educacional" }, { value: "inspirador", label: "Inspirador / Motivacional" }, { value: "divertido", label: "Divertido / Humorístico" }, { value: "tecnico", label: "Técnico / Especializado" },
                    ]
                },
                { id: "step6_q1", type: "textarea", rows: 3, label: "Descreva brevemente o estilo desejado.", description: "Adicione nuances ao tom selecionado.", placeholder: "Ex: Conversa amigável, mas direta ao ponto...", tooltip: "Pense em adjetivos que definam a escrita.", aiSuggestion: { type: 'writingStyle', buttonText: 'Sugerir Estilo', countDefault: 3} },
                { id: "step6_q2", type: "textarea", rows: 4, label: "Como você garantirá clareza e coesão entre os capítulos?", description: "Pense em elementos de ligação, resumos, e fluxo lógico.", placeholder: "Ex: Usar introduções e conclusões curtas...", tooltip: "Facilite a leitura e a compreensão do conteúdo como um todo." }
            ]
        },
        // --- Step 8: Revisão ---
        {
            title: "8. Processo de Revisão e Edição",
            questions: [
                {
                    id: "step7_q0", type: "checkbox", label: "Quais etapas de revisão você planeja realizar?", description: "Marque todas as etapas previstas. Recomenda-se múltiplas revisões.", tooltip: "Uma boa revisão é crucial para a qualidade final.", required: true,
                    options: [
                        { value: "auto_conteudo", label: "Auto-revisão focada em Conteúdo e Estrutura" }, { value: "auto_gramatica", label: "Auto-revisão focada em Gramática e Ortografia" }, { value: "leitura_voz_alta", label: "Leitura em voz alta (pega erros de fluidez)" }, { value: "revisor_amigo", label: "Revisão por colega ou amigo (leitor beta)" }, { value: "revisor_profissional", label: "Contratação de Revisor Profissional" }, { value: "editor_profissional", label: "Contratação de Editor Profissional (mais profundo que revisão)" },
                    ],
                    otherOption: true
                },
                {
                    id: "step7_q1", type: "checkbox", label: "Quais ferramentas de apoio pretende utilizar?", description: "Marque as ferramentas que auxiliarão no processo.", tooltip: "Ferramentas podem otimizar a revisão, mas não substituem a leitura atenta.",
                    options: [
                        { value: "corretor_word", label: "Corretor Ortográfico/Gramatical (Word, Docs, etc.)" }, { value: "grammarly", label: "Ferramentas Avançadas (Grammarly, LanguageTool, etc.)" }, { value: "plagio", label: "Verificador de Plágio" }, { value: "dicionario", label: "Dicionários (Sinônimos, Significados)" }, { value: "manual_estilo", label: "Manual de Estilo (próprio ou de mercado)" },
                    ],
                    otherOption: true
                }
            ]
        },
        // --- Step 9: Design ---
        {
            title: "9. Design, Formatação e Formato Final",
            questions: [
                {
                    id: "step8_q0", type: "checkbox", label: "Quais serão os formatos finais de entrega?", description: "Selecione todos os formatos que serão disponibilizados.", tooltip: "Considere onde e como seus leitores preferem ler. PDF é universal.", required: true,
                    options: [
                        { value: "pdf", label: "PDF (Layout fixo, ideal para impressão e visualização universal)" }, { value: "epub", label: "EPUB (Layout fluido, padrão para e-readers/apps, exceto Kindle)" }, { value: "mobi", label: "MOBI / AZW3 (Layout fluido, formato para Kindle - Amazon)" }, { value: "web", label: "Versão Online / HTML (Acessível via navegador)" }
                    ],
                    otherOption: true
                },
                { id: "step8_q1", type: "textarea", rows: 6, label: "Descreva o estilo visual e a formatação desejada. (Use o editor abaixo)", description: "Pense em layout, fontes, cores, uso de imagens, gráficos, etc.", placeholder: "Ex: Design moderno e limpo, com cores da minha marca...", tooltip: "O design impacta a experiência de leitura e a percepção de valor." },
                {
                    id: "step8_q2", type: "radio", label: "Quem fará o design e a formatação final?", description: "Seja realista sobre suas habilidades, tempo e orçamento.", tooltip: "Um design profissional pode fazer a diferença.", required: true,
                    options: [
                         { value: "diy_basico", label: "Eu mesmo (DIY básico - Word/Docs)" }, { value: "diy_template", label: "Eu mesmo (Usando template - Canva, InDesign Template, etc.)" }, { value: "freelancer", label: "Contratar Freelancer (Designer/Diagramador)" }, { value: "agencia", label: "Contratar Agência Especializada" },
                    ]
                }
            ]
        },
        // --- Step 10: Capa ---
        {
            title: "10. Criação da Capa Impactante",
            questions: [
                { id: "step9_q0", type: "textarea", rows: 4, label: "Descreva a ideia visual para a capa.", description: "Pense em cores, imagens, fontes e o sentimento que deseja transmitir.", placeholder: "Ex: Fundo azul escuro, título grande...", tooltip: "A capa é a primeira impressão – deve ser atraente, legível em miniatura e profissional.", aiSuggestion: { type: 'coverConcept', buttonText: 'Sugerir Conceitos', countDefault: 2} },
                {
                    id: "step9_q1", type: "radio", label: "Quem criará a capa?", description: "Considere a importância da capa para a atratividade do eBook.", tooltip: "Investir em uma boa capa geralmente vale a pena.", required: true,
                    options: [
                        { value: "diy_canva", label: "Eu mesmo (DIY - Canva ou similar)" }, { value: "freelancer_design", label: "Contratar Freelancer (Designer Gráfico)" }, { value: "freelancer_capista", label: "Contratar Capista Especializado" }, { value: "agencia", label: "Contratar Agência" }, { value: "designer_interno", label: "Designer da minha equipe/empresa" }
                    ]
                }
            ]
        },
        // --- Step 11: Divulgação ---
        {
            title: "11. Estratégia de Divulgação e Lançamento",
            questions: [
                {
                    id: "step10_q0", type: "radio", label: "Qual será o modelo principal de distribuição?", description: "Como o leitor terá acesso ao eBook?", tooltip: "Defina como seu eBook chegará ao público.", required: true,
                    options: [
                         { value: "gratuito_site", label: "Gratuito (Download direto no site/blog)" }, { value: "isca_digital", label: "Isca Digital (Gratuito em troca de email/contato)" }, { value: "pago_amazon", label: "Pago (Venda na Amazon KDP)" }, { value: "pago_hotmart", label: "Pago (Venda em plataformas - Hotmart, Eduzz, etc.)" }, { value: "pago_proprio", label: "Pago (Venda direta no próprio site)" },
                    ],
                    otherOption: true
                },
                {
                    id: "step10_q1", type: "checkbox", label: "Quais canais de marketing e divulgação você planeja usar?", description: "Marque todas as estratégias que pretende implementar.", tooltip: "Um bom eBook merece uma boa divulgação. Combine canais!", required: true,
                    options: [
                        { value: "email_mkt", label: "Email Marketing (para lista existente)" }, { value: "social_organico", label: "Redes Sociais (Posts orgânicos)" }, { value: "social_ads", label: "Anúncios Pagos (Facebook/Instagram Ads, Google Ads)" }, { value: "blog_seo", label: "Conteúdo de Blog / SEO" }, { value: "parcerias", label: "Parcerias / Influenciadores" }, { value: "webinar", label: "Webinar / Evento de Lançamento" }, { value: "assessoria", label: "Assessoria de Imprensa / Mídia" }, { value: "grupos", label: "Grupos / Comunidades Online" },
                    ],
                    otherOption: true,
                    aiSuggestion: { type: 'marketingChannels', buttonText: 'Sugerir Canais', countDefault: 5}
                },
                { id: "step10_q2", type: "textarea", rows: 3, label: "Detalhe a principal ação de lançamento.", description: "Qual será o 'grande evento' ou foco inicial da divulgação?", placeholder: "Ex: Semana de lançamento com lives diárias...", tooltip: "Tenha um plano claro para o momento do lançamento.", aiSuggestion: { type: 'launchAction', buttonText: 'Sugerir Ação', countDefault: 2} }
            ]
        },
        // --- Step 12: Pós-Lançamento ---
        {
            title: "12. Pós-Lançamento e Atualizações",
            questions: [
                {
                    id: "step11_q0", type: "radio", label: "Você planeja revisar ou atualizar o conteúdo no futuro?", description: "Um eBook pode precisar de atualizações, especialmente em temas dinâmicos.", tooltip: "Manter o conteúdo relevante aumenta sua longevidade e valor.",
                    options: [
                         { value: "sim_regular", label: "Sim, regularmente (ex: anualmente, semestralmente)" }, { value: "sim_conforme", label: "Sim, conforme necessário (grandes mudanças no tema)" }, { value: "talvez", label: "Talvez, dependendo do feedback e desempenho" }, { value: "nao", label: "Não, o conteúdo é atemporal / não há planos de atualização" }
                    ]
                },
                { id: "step11_q1", type: "text", label: "Se sim, qual a frequência estimada de atualização?", description:"(Opcional) Especifique o intervalo se escolheu 'Sim, regularmente'.", placeholder:"Ex: Anualmente; A cada 6 meses", tooltip:"Ajuda a planejar a manutenção."},
                {
                    id: "step11_q2", type: "checkbox", label: "Como você coletará feedback dos leitores?", description: "Marque as formas de ouvir seu público.", tooltip: "O feedback é valioso para melhorias contínuas e novas ideias.",
                    options: [
                         { value: "form_ebook", label: "Link para Formulário de Feedback dentro do eBook" }, { value: "email_pos", label: "Email Pós-Download/Compra solicitando feedback" }, { value: "comentarios_site", label: "Monitorar Comentários no site/blog" }, { value: "comentarios_venda", label: "Monitorar Avaliações/Comentários na plataforma de venda" }, { value: "redes_sociais", label: "Monitorar Menções em Redes Sociais" }, { value: "pesquisa_lista", label: "Enviar Pesquisa para a lista de emails" },
                    ],
                    otherOption: true
                }
            ]
        }
    ];
    const ebookTemplates = {
        "guias_educacionais": {
            name: "Guias Educacionais e Tutoriais",
            subcategories: {
                "tecnologia_software": {
                    name: "Tecnologia e Software",
                    templates: {
                        "guia_saas": {
                            name: "Guia Completo de [Software SaaS]",
                            data: {
                                "step0_q0": "Guia Definitivo do [Nome do Software SaaS] para Iniciantes e Usuários Intermediários",
                                "step0_q1": "Ajudar novos e existentes usuários a dominar as funcionalidades essenciais do [Nome do Software SaaS], otimizar seu fluxo de trabalho e extrair o máximo valor da ferramenta.",
                                "step0_q2": "Posicionar nossa marca como especialista em [Área do Software], educar nossa base de usuários e atrair novos clientes interessados em produtividade com [Nome do Software SaaS].",
                                "step1_q0": "<p><strong>Persona Primária:</strong> Joana, 32 anos, gerente de projetos em uma PME. Precisa implementar e treinar sua equipe no [Nome do Software SaaS] para melhorar a colaboração e o acompanhamento de tarefas. Desafios: Tempo limitado, equipe com diferentes níveis de familiaridade tecnológica. Objetivos: Aumentar a eficiência da equipe, ter relatórios claros de progresso. Busca informação em blogs de produtividade, tutoriais em vídeo e fóruns do software.</p><p><strong>Persona Secundária:</strong> Carlos, 25 anos, freelancer de marketing digital. Quer usar o [Nome do Software SaaS] para gerenciar múltiplos clientes e projetos. Desafios: Organizar demandas, manter clientes atualizados. Objetivos: Escalar seus serviços, parecer mais profissional. Busca tutoriais rápidos e dicas avançadas.</p>",
                                "step1_q1": "misto",
                                "step1_q2": "Dificuldade em entender todas as funcionalidades do [Nome do Software SaaS] e como aplicá-las eficientemente no dia a dia, resultando em subutilização da ferramenta ou processos manuais demorados.",
                                "step2_q0": "O leitor será capaz de configurar o [Nome do Software SaaS] do zero, gerenciar projetos/tarefas/recursos [dependendo do core do software], colaborar efetivamente com sua equipe, gerar relatórios básicos e conhecer as melhores práticas para [principal benefício do software].",
                                "step2_q1": "educar",
                                "step3_q0": "Desvendando o [Nome do Software SaaS]: Seu Guia Prático para Máxima Produtividade",
                                "step3_q1": "Do Básico ao Avançado: Domine Ferramentas, Fluxos e Segredos para Transformar seu Trabalho",
                                "step3_q2": "Este eBook é o seu passaporte para dominar o [Nome do Software SaaS], transformando-o de uma simples ferramenta em um poderoso aliado da sua produtividade e organização.",
                                "step4_q0": "Introdução: Por que o [Nome do Software SaaS] e o que esperar deste guia?\nCapítulo 1: Primeiros Passos – Configuração e Interface\nCapítulo 2: Dominando [Funcionalidade Core 1 – Ex: Gerenciamento de Tarefas]\nCapítulo 3: Explorando [Funcionalidade Core 2 – Ex: Colaboração em Equipe]\nCapítulo 4: [Funcionalidade Core 3 – Ex: Relatórios e Análises]\nCapítulo 5: Dicas Pro e Truques Escondidos para Usuários Avançados\nCapítulo 6: Integrando o [Nome do Software SaaS] com Outras Ferramentas\nConclusão: Próximos Passos e Mantendo-se Atualizado",
                                "step4_q1": "<h2>Capítulo 1: Primeiros Passos – Configuração e Interface</h2><ul><li>Criando sua conta e entendendo os planos</li><li>Visão geral do dashboard principal</li><li>Personalizando suas preferências e notificações</li><li>Convidando membros da equipe e gerenciando acessos</li></ul><p>&nbsp;</p><h2>Capítulo 2: Dominando [Funcionalidade Core 1]</h2><ul><li>Criando e atribuindo [itens da funcionalidade]</li><li>Definindo prazos, prioridades e dependências</li><li>Utilizando visualizações (Kanban, Lista, Calendário)</li><li>Templates de [itens da funcionalidade] para agilizar</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_glossario": "on",
                                "step6_q0": "didatico",
                                "step6_q1": "Claro, objetivo, com exemplos práticos e screenshots (a serem adicionados no design). Linguagem acessível, mas precisa.",
                                "step8_q0_pdf": "on", "step8_q0_web": "on",
                                "step10_q0": "gratuito_site",
                                "step10_q1_email_mkt": "on", "step10_q1_blog_seo": "on", "step10_q1_social_organico": "on"
                            }
                        }
                    }
                },
                "desenvolvimento_pessoal": {
                    name: "Desenvolvimento Pessoal",
                    templates: {
                        "gestao_tempo": {
                            name: "Dominando a Arte da Gestão do Tempo",
                            data: {
                                "step0_q0": "Gestão Eficaz do Tempo para Profissionais Ocupados",
                                "step0_q1": "Ajudar profissionais a superar a procrastinação, organizar suas prioridades e encontrar mais tempo para o que realmente importa, tanto na vida profissional quanto pessoal.",
                                "step0_q2": "Compartilhar técnicas comprovadas de gestão do tempo que transformaram minha própria produtividade e bem-estar, inspirando outros a alcançar o mesmo.",
                                "step1_q0": "<p><strong>Persona:</strong> Ana, 40 anos, empreendedora e mãe de dois filhos. Sente-se constantemente sobrecarregada, com dificuldade de equilibrar as demandas do negócio e da família. Desafios: Interrupções constantes, dificuldade em dizer não, cansaço. Objetivos: Ter mais controle sobre seu dia, reduzir o estresse, ter tempo para si mesma. Busca informação em livros de autoajuda, podcasts sobre produtividade e artigos online.</p>",
                                "step1_q1": "misto",
                                "step1_q2": "Sentimento de estar sempre 'correndo atrás', sem conseguir finalizar tarefas importantes ou ter tempo para atividades prazerosas e de autocuidado, levando ao estresse e burnout.",
                                "step2_q0": "O leitor será capaz de identificar seus 'ladrões de tempo', aplicar técnicas como Matriz de Eisenhower e Pomodoro, planejar sua semana eficientemente, delegar tarefas e estabelecer limites saudáveis para proteger seu tempo.",
                                "step2_q1": "inspirar",
                                "step3_q0": "Tempo Rei: Conquiste Sua Agenda e Transforme Sua Vida",
                                "step3_q1": "Um Guia Prático com Técnicas Comprovadas para Você Parar de Correr e Começar a Viver",
                                "step3_q2": "Este eBook oferece um arsenal de estratégias práticas para você retomar o controle do seu tempo, aumentar sua produtividade e, o mais importante, viver uma vida com mais propósito e menos estresse.",
                                "step4_q0": "Introdução: A Ilusão da Falta de Tempo\nCapítulo 1: Autoconhecimento – Entendendo Seu Uso do Tempo Atual\nCapítulo 2: Definindo Prioridades Claras – O que Realmente Importa?\nCapítulo 3: Ferramentas e Técnicas de Planejamento Semanal e Diário\nCapítulo 4: Vencendo a Procrastinação e Mantendo o Foco\nCapítulo 5: A Arte de Dizer Não e Delegar Tarefas\nCapítulo 6: Gerenciando Energia, Não Apenas Tempo\nCapítulo 7: Criando Hábitos Sustentáveis de Gestão do Tempo\nConclusão: Seu Novo Relacionamento com o Tempo",
                                "step4_q1": "<h2>Capítulo 1: Autoconhecimento</h2><ul><li>Registrando suas atividades (Time Log)</li><li>Identificando seus maiores desperdiçadores de tempo</li><li>Entendendo seus picos de produtividade (cronotipo)</li></ul><p>&nbsp;</p><h2>Capítulo 2: Definindo Prioridades</h2><ul><li>A Matriz de Eisenhower (Urgente vs. Importante)</li><li>Técnica MoSCoW (Must have, Should have, Could have, Won't have)</li><li>Alinhando suas tarefas com seus objetivos de longo prazo</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_cta": "on",
                                "step6_q0": "inspirador",
                                "step6_q1": "Empático, motivador, com histórias reais (anonimizadas ou do autor) e exercícios práticos. Linguagem positiva e encorajadora.",
                                "step8_q0_pdf": "on", "step8_q0_epub": "on",
                                "step10_q0": "isca_digital",
                                "step10_q1_social_organico": "on", "step10_q1_webinar": "on", "step10_q1_parcerias": "on"
                            }
                        }
                    }
                }
            }
        },
        "marketing_negocios": {
            name: "Marketing e Negócios",
            subcategories: {
                "geracao_leads": {
                    name: "Geração de Leads e Vendas",
                    templates: {
                        "email_mkt_avancado": {
                            name: "Estratégias Avançadas de Email Marketing",
                            data: {
                                "step0_q0": "Email Marketing Avançado para Máxima Conversão",
                                "step0_q1": "Capacitar profissionais de marketing e donos de negócios a criar campanhas de email marketing altamente eficazes que nutrem leads, aumentam o engajamento e geram mais vendas.",
                                "step0_q2": "Consolidar nossa agência como referência em automação de marketing e funis de venda, gerando leads qualificados para nossos serviços de consultoria.",
                                "step1_q0": "<p><strong>Persona:</strong> Ricardo, 45 anos, diretor de marketing de uma empresa de médio porte no setor B2B. Já utiliza email marketing básico, mas sente que suas campanhas não estão performando bem. Desafios: Baixas taxas de abertura e clique, dificuldade em segmentar a base, não sabe como criar fluxos de nutrição eficazes. Objetivos: Aumentar o ROI do email marketing, gerar mais SQLs (Sales Qualified Leads). Busca informação em blogs de marketing, webinars de ferramentas e cases de sucesso.</p>",
                                "step1_q1": "intermediario",
                                "step1_q2": "Dificuldade em transformar uma lista de emails em clientes pagantes, com campanhas genéricas que não engajam e não levam o lead pela jornada de compra de forma eficiente.",
                                "step2_q0": "O leitor será capaz de planejar funis de email marketing, segmentar sua base de forma inteligente, escrever copy persuasiva para emails, criar fluxos de automação para nutrição e vendas, analisar métricas chave e otimizar suas campanhas continuamente.",
                                "step2_q1": "gerar_leads",
                                "step3_q0": "Email Marketing que Converte: Do Lead ao Cliente Fiel",
                                "step3_q1": "O Guia Definitivo com Estratégias, Automações e Copywriting para Multiplicar Suas Vendas",
                                "step3_q2": "Transforme seu email marketing em uma máquina de vendas com este guia completo, repleto de táticas avançadas e exemplos práticos para engajar e converter leads.",
                                "step4_q0": "Introdução: O Poder Subestimado do Email Marketing Moderno\nCapítulo 1: Planejamento Estratégico: Funis e Jornada do Cliente\nCapítulo 2: Construção e Higienização de Listas de Email de Qualidade\nCapítulo 3: Segmentação Avançada: Entregando a Mensagem Certa para a Pessoa Certa\nCapítulo 4: Copywriting para Emails: Escrevendo Assuntos e Conteúdos Irresistíveis\nCapítulo 5: Design e Layout de Emails que Performam\nCapítulo 6: Automação de Marketing: Criando Fluxos Inteligentes de Nutrição e Venda\nCapítulo 7: Testes A/B e Otimização Contínua de Campanhas\nCapítulo 8: Métricas Essenciais: Analisando Resultados e Calculando ROI\nConclusão: O Futuro do Email Marketing e Seus Próximos Passos",
                                "step4_q1": "<h2>Capítulo 6: Automação de Marketing</h2><ul><li>Tipos de fluxos de automação (boas-vindas, abandono de carrinho, nutrição de leads, reengajamento)</li><li>Gatilhos e condições para iniciar e mover leads nos fluxos</li><li>Personalização dinâmica de conteúdo em emails automatizados</li><li>Ferramentas populares de automação de email marketing</li></ul><p>&nbsp;</p><h2>Capítulo 4: Copywriting para Emails</h2><ul><li>Estrutura de um email persuasivo (AIDA, PAS)</li><li>Técnicas para escrever assuntos que aumentam a taxa de abertura</li><li>Uso de gatilhos mentais e storytelling em emails</li><li>CTAs (Call to Actions) eficazes para diferentes objetivos</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_cta": "on",
                                "step6_q0": "profissional",
                                "step6_q1": "Direto ao ponto, focado em resultados, com jargões de marketing explicados. Exemplos práticos e estudos de caso (hipotéticos ou reais anonimizados).",
                                "step8_q0_pdf": "on",
                                "step8_q2": "diy_template",
                                "step10_q0": "isca_digital",
                                "step10_q1_email_mkt": "on", "step10_q1_social_ads": "on", "step10_q1_blog_seo": "on", "step10_q1_webinar": "on",
                                "step11_q0": "sim_conforme"
                            }
                        }
                    }
                },
                "branding_conteudo": {
                    name: "Branding e Conteúdo",
                    templates: {
                         "marca_pessoal_forte": {
                            name: "Construindo uma Marca Pessoal Forte Online",
                            data: {
                                "step0_q0": "Construção e Fortalecimento de Marca Pessoal no Ambiente Digital",
                                "step0_q1": "Guiar profissionais e empreendedores no processo de definir, construir e comunicar uma marca pessoal autêntica e impactante online, que gere autoridade e oportunidades.",
                                "step0_q2": "Compartilhar minha jornada e aprendizados na construção da minha própria marca pessoal, ajudando outros a evitar erros comuns e acelerar seu crescimento.",
                                "step1_q0": "<p><strong>Persona:</strong> Laura, 28 anos, consultora de RH recém-formada. Quer se destacar no mercado e atrair clientes para seus serviços de consultoria. Desafios: Não sabe por onde começar, medo de se expor, dificuldade em definir seu nicho. Objetivos: Ser reconhecida como especialista, conseguir seus primeiros clientes, construir uma rede de contatos. Busca inspiração em perfis de sucesso no LinkedIn, blogs sobre carreira e marketing pessoal.</p>",
                                "step1_q1": "iniciante",
                                "step1_q2": "Sentir-se 'invisível' no mercado digital, com dificuldade de comunicar seu valor único e atrair as oportunidades certas, resultando em pouca diferenciação e crescimento lento.",
                                "step2_q0": "O leitor será capaz de identificar seus talentos e paixões, definir seu nicho e proposta de valor, criar uma identidade visual e verbal consistente, escolher as plataformas digitais certas, produzir conteúdo relevante e construir uma rede de contatos estratégica.",
                                "step2_q1": "construir_autoridade",
                                "step3_q0": "Marca Pessoal Imparável: De Anônimo a Referência no Seu Nicho",
                                "step3_q1": "O Guia Passo a Passo para Construir Sua Autoridade Online, Atrair Oportunidades e Deixar Sua Marca no Mundo",
                                "step3_q2": "Descubra como transformar sua paixão e conhecimento em uma marca pessoal magnética que abre portas e te posiciona como líder no seu mercado com este guia prático.",
                                "step4_q0": "Introdução: A Era da Marca Pessoal – Por que Você Precisa de Uma?\nCapítulo 1: Autoconhecimento Profundo: A Base da Sua Marca\nCapítulo 2: Definindo Seu Nicho e Proposta Única de Valor (PUV)\nCapítulo 3: Identidade Visual e Verbal: Comunicando Quem Você É\nCapítulo 4: Escolhendo Suas Plataformas Digitais Estratégicas (LinkedIn, Instagram, Blog, etc.)\nCapítulo 5: Marketing de Conteúdo para Marca Pessoal: Criando Valor e Engajamento\nCapítulo 6: Networking Estratégico Online e Offline\nCapítulo 7: Monetizando Sua Marca Pessoal: Gerando Renda com Sua Expertise\nCapítulo 8: Lidando com Críticas e Mantendo a Autenticidade\nConclusão: Sua Marca Pessoal em Evolução Contínua",
                                "step4_q1": "<h2>Capítulo 1: Autoconhecimento Profundo</h2><ul><li>Identificando seus talentos, paixões e valores</li><li>Análise SWOT pessoal (Forças, Fraquezas, Oportunidades, Ameaças)</li><li>Descobrindo seu 'porquê' (Golden Circle de Simon Sinek)</li><li>Coletando feedback sobre sua imagem atual</li></ul><p>&nbsp;</p><h2>Capítulo 5: Marketing de Conteúdo para Marca Pessoal</h2><ul><li>Formatos de conteúdo ideais para cada plataforma</li><li>Pilares de conteúdo e calendário editorial</li><li>Técnicas de storytelling para conectar com a audiência</li><li>Como promover seu conteúdo e aumentar o alcance</li></ul>",
                                "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_sobre_autor": "on",
                                "step6_q0": "informal",
                                "step6_q1": "Conversacional, inspirador, com exercícios práticos e prompts para reflexão. Histórias de sucesso (reais ou fictícias) para ilustrar conceitos.",
                                "step8_q0_pdf": "on", "step8_q0_epub": "on", "step8_q0_mobi": "on",
                                "step10_q0": "pago_hotmart",
                                "step10_q1_social_organico": "on", "step10_q1_social_ads": "on", "step10_q1_parcerias": "on", "step10_q1_webinar": "on",
                                "step11_q0": "sim_regular", "step11_q1": "Anualmente"
                            }
                        }
                    }
                }
            }
        },
        "culinaria_estilovida": {
            name: "Culinária e Estilo de Vida",
            // No subcategories for this example, templates directly under category
            templates: {
                "receitas_veganas_rapidas": {
                    name: "Receitas Veganas Rápidas para o Dia a Dia",
                     data: {
                        "step0_q0": "Culinária Vegana Prática e Saborosa para Iniciantes",
                        "step0_q1": "Mostrar que a culinária vegana pode ser deliciosa, acessível e rápida, desmistificando a ideia de que é complicada ou sem graça, e ajudando pessoas a incluir mais refeições à base de plantas em sua rotina.",
                        "step0_q2": "Compartilhar minha paixão pela culinária vegana, tornando-a mais acessível e inspirando um estilo de vida mais saudável e sustentável, além de construir uma comunidade online em torno do tema.",
                        "step1_q0": "<p><strong>Persona:</strong> Mariana, 29 anos, profissional de marketing que trabalha em home office. Quer adotar uma alimentação mais saudável e reduzir o consumo de carne, mas tem pouco tempo para cozinhar e se sente intimidada por receitas complexas. Desafios: Falta de tempo, pouca familiaridade com ingredientes veganos, medo de comidas sem sabor. Objetivos: Comer de forma mais saudável, aprender receitas veganas fáceis, sentir-se mais energizada. Busca inspiração no Instagram, Pinterest e blogs de culinária.</p>",
                        "step1_q1": "iniciante",
                        "step1_q2": "Dificuldade em encontrar receitas veganas que sejam ao mesmo tempo rápidas, fáceis de preparar com ingredientes acessíveis e verdadeiramente saborosas, levando à desistência ou frustração.",
                        "step2_q0": "O leitor será capaz de preparar mais de [Número] receitas veganas deliciosas para café da manhã, almoço, jantar e lanches em menos de 30 minutos cada, entender substituições básicas de ingredientes e montar uma lista de compras vegana essencial.",
                        "step2_q1": "educar",
                        "step3_q0": "Vegano Express: Sabor e Praticidade na Sua Cozinha em Minutos",
                        "step3_q1": "[Número]+ Receitas Deliciosas e Rápidas para Descomplicar Sua Alimentação à Base de Plantas",
                        "step3_q2": "Descubra como a culinária vegana pode ser incrivelmente fácil, rápida e cheia de sabor com este eBook repleto de receitas testadas e aprovadas para o seu dia a dia corrido.",
                        "step4_q0": "Introdução: Bem-vindo ao Mundo Delicioso da Culinária Vegana Rápida!\nCapítulo 1: Despensa Vegana Inteligente: Ingredientes Essenciais e Onde Encontrá-los\nCapítulo 2: Café da Manhã Energizante em Minutos (Ex: Smoothies, Mingaus, Tostas)\nCapítulo 3: Almoços Leves e Nutritivos (Ex: Saladas Completas, Wraps, Sopas Rápidas)\nCapítulo 4: Jantares Saborosos e Práticos (Ex: Massas de Panela Única, Curries Express, Mexidos)\nCapítulo 5: Lanches e Belisquetes Saudáveis (Ex: Pastinhas, Bolachas Caseiras, Frutas Turbinadas)\nCapítulo 6: Dicas Extras: Congelamento, Reaproveitamento e Planejamento Semanal\nConclusão: Sua Jornada Vegana Deliciosa Continua!",
                        "step4_q1": "<h2>Capítulo 2: Café da Manhã Energizante em Minutos</h2><ul><li>Smoothie Verde Detox Power</li><li>Overnight Oats Cremoso com Frutas Vermelhas</li><li>Tosta de Abacate Turbinada com Grão de Bico Crocante</li><li>Panqueca Vegana de Banana (3 ingredientes)</li></ul><p>&nbsp;</p><h2>Capítulo 4: Jantares Saborosos e Práticos</h2><ul><li>Macarrão Cremoso de Abobrinha com Molho de Tomate Caseiro Rápido</li><li>Curry Indiano de Lentilha Vermelha (Pronto em 20 minutos)</li><li>Tacos Veganos Divertidos com Feijão Preto e Guacamole</li><li>Arroz Frito Asiático com Tofu e Legumes</li></ul>",
                        "step4_q2_introducao": "on", "step4_q2_conclusao": "on", "step4_q2_recursos": "on", "step4_q2_glossario": "on", "step4_q2_sobre_autor": "on",
                        "step6_q0": "informal",
                        "step6_q1": "Amigável, encorajador, como uma conversa com um amigo que adora cozinhar. Instruções claras e simples. Fotos vibrantes (a serem adicionadas no design).",
                        "step8_q0_pdf": "on", "step8_q0_epub": "on",
                        "step8_q1": "<p>Design limpo, moderno e apetitoso. Uso de cores vibrantes e fontes legíveis. Muitas fotos de alta qualidade das receitas. Layout que facilite a leitura rápida dos ingredientes e modo de preparo, talvez com ícones para tempo de preparo e dificuldade.</p>",
                        "step8_q2": "freelancer",
                        "step9_q0": "Capa com uma foto bem colorida e apetitosa de uma das receitas principais. Título grande e chamativo. Nome do autor em destaque. Cores alegres e que remetam à alimentação saudável (verdes, laranjas, amarelos).",
                        "step9_q1": "freelancer_design",
                        "step10_q0": "pago_amazon",
                        "step10_q1_social_organico": "on", "step10_q1_social_ads": "on", "step10_q1_blog_seo": "on", "step10_q1_parcerias": "on",
                        "step11_q0": "sim_conforme",
                        "step11_q2_form_ebook": "on", "step11_q2_comentarios_venda": "on", "step11_q2_redes_sociais": "on"
                     }
                }
            }
        }
    };

    // --- WIZARD State and DOM Elements ---
    let currentStep = 0;
    let tooltipList = [];
    let collectedFormData = {};
    let autoSaveTimer = null;

    const stepsContainer = document.getElementById('stepsContainer');
    const progressIndicator = document.getElementById('progressIndicator');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const wizardForm = document.getElementById('wizardForm');
    const wizardContainer = document.getElementById('wizardContainer');
    const completionSection = document.getElementById('completionSection');
    const downloadBtn = document.getElementById('downloadBtn');
    const reportThemeSelector = document.getElementById('reportThemeSelector');
    const reportFormatSelector = document.getElementById('reportFormatSelector');
    const apiKeyStatusContainer = document.getElementById('apiKeyStatusContainer');
    const saveProgressBtn = document.getElementById('saveProgressBtn');
    const resetPlanBtn = document.getElementById('resetPlanBtn');
    const validationErrorEl = document.getElementById('validationErrorMessage');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // --- AI Integration Variables ---
    let geminiAPIKey = '';
    let genAI;
    let aiModel;
    let aiEnabled = false;
    const GEMINI_MODEL_NAME = "gemini-1.5-flash-latest";

    // --- Template Variables and DOM ---
    const templateDropdownMenu = document.getElementById('templateDropdownMenu');

    // --- AI Assistance Modal DOM Elements & State ---
    const aiAssistanceModalEl = document.getElementById('aiAssistanceModal');
    const aiAssistanceModalInstance = new bootstrap.Modal(aiAssistanceModalEl);
    const aiSuggestionCountModalEl = document.getElementById('aiSuggestionCountModal');
    const aiAssistanceOutputEl = document.getElementById('aiAssistanceOutput');
    const aiApplyOutputBtn = document.getElementById('aiApplyOutputBtn');
    const aiDiscardOutputBtn = document.getElementById('aiDiscardOutputBtn');
    const aiCopyOutputBtn = document.getElementById('aiCopyOutputBtn');
    const aiCloseModalBtn = document.getElementById('aiCloseModalBtn');

    let currentAiModalOutput = "";
    let modalAiTargetFieldId = null;
    let modalAiIsCkEditorTarget = false;
    let modalAiFriendlyActionName = "";

    // --- INLINE AI ASSISTANCE FOR CKEDITOR ---
    const inlineAiFloatingButtonId = 'inlineAiFloatingButton';
    let inlineAiFloatingButton = null;
    let currentInlineEditorInstance = null; // Stores the CKEditor instance that has the current selection
    let currentInlineEditorId = null; // Stores the ID of the CKEditor (e.g., 'step1_q0')
    let inlineAiDropdown = null; // Bootstrap Dropdown instance for the floating button
    let debounceTimerInlineButton;


    const inlineAiActions = [
        {
            id: 'expandPoint',
            label: 'Expandir este ponto',
            icon: 'bi-arrows-angle-expand',
            promptBuilderKey: 'expandPoint',
            insertionMode: 'afterOrReplace' // 'replace', 'after', 'before', 'showSuggestionsList'
        },
        {
            id: 'rewriteTone',
            label: 'Reescrever com tom...',
            icon: 'bi-arrow-repeat',
            isSubmenu: true,
            subActions: [ /* Populated by populateRewriteToneSubmenu */ ],
        },
        {
            id: 'simplifyLanguage',
            label: 'Simplificar linguagem',
            icon: 'bi-card-text', // Changed icon for variety
            promptBuilderKey: 'simplifyLanguage',
            insertionMode: 'replace'
        },
        {
            id: 'suggestAlternatives',
            label: 'Sugerir alternativas',
            icon: 'bi-lightbulb',
            promptBuilderKey: 'suggestAlternatives',
            insertionMode: 'showSuggestionsList'
        }
    ];

    const inlineAiPrompts = {
        expandPoint: (selectedText, context) => `Você é um assistente de escrita conciso e direto. Expanda o seguinte ponto/frase de forma detalhada (adicione 2-3 frases relevantes ou um parágrafo curto), mantendo o tom ${context.tone || 'neutro'} e o foco no tema "${context.theme || 'não definido'}".\n\nTexto Original: "${selectedText}"\n\nResultado Esperado: Forneça APENAS o texto expandido ou o conteúdo adicional. Não inclua frases como "Claro, aqui está a expansão:" ou repita o texto original desnecessariamente.`,
        rewriteTone: (selectedText, newTone, context) => `Reescreva o seguinte texto com um tom ${newTone}, considerando o tema "${context.theme || 'não definido'}" e o público "${context.audienceLevel || 'geral'}".\n\nTexto Original: "${selectedText}"\n\nResultado Esperado: Forneça APENAS o texto reescrito no novo tom.`,
        simplifyLanguage: (selectedText, context) => `Simplifique a linguagem do seguinte texto, tornando-o mais claro, conciso e acessível para um público ${context.audienceLevel || 'geral'} interessado no tema "${context.theme || 'não definido'}".\n\nTexto Original: "${selectedText}"\n\nResultado Esperado: Forneça APENAS o texto simplificado.`,
        suggestAlternatives: (selectedText, count = 3, context) => `Sugira ${count} alternativas concisas e impactantes para a seguinte frase/título, que está relacionada ao tema "${context.theme || 'não definido'}".\n\nTexto Original: "${selectedText}"\n\nInstruções: Liste cada alternativa em uma nova linha. Não use marcadores (como -, *, 1.) ou qualquer texto introdutório. Apenas as alternativas, uma por linha.`
    };

    function populateRewriteToneSubmenu() {
        const toneQuestion = steps.find(s => s.title.startsWith("7."))?.questions.find(q => q.id === 'step6_q0');
        if (toneQuestion && toneQuestion.options) {
            const rewriteToneAction = inlineAiActions.find(a => a.id === 'rewriteTone');
            if (rewriteToneAction) {
                rewriteToneAction.subActions = toneQuestion.options
                    .filter(opt => opt.value)
                    .map(opt => ({
                        id: `rewriteTone_${opt.value}`,
                        label: opt.label,
                        icon: 'bi-mic', // Placeholder icon for sub-action
                        originalToneValue: opt.value, // Store the original value for the prompt
                        promptBuilderKey: 'rewriteTone', // Uses the generic rewriteTone prompt builder
                        insertionMode: 'replace'
                    }));
            }
        }
    }


    function createInlineAiFloatingButton() {
        if (document.getElementById(inlineAiFloatingButtonId)) {
            inlineAiFloatingButton = document.getElementById(inlineAiFloatingButtonId);
        } else {
            inlineAiFloatingButton = document.createElement('div'); // Use div for easier styling as button group
            inlineAiFloatingButton.id = inlineAiFloatingButtonId;
            // Initial classes for Bootstrap dropdown structure. 'd-none' to hide initially.
            inlineAiFloatingButton.className = 'btn-group d-none';
            inlineAiFloatingButton.style.position = 'absolute';
            inlineAiFloatingButton.style.zIndex = '1056';
            inlineAiFloatingButton.setAttribute('role', 'group');

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-primary rounded-circle p-0';
            button.style.width = '32px';
            button.style.height = '32px';
            button.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.innerHTML = `<i class="bi bi-magic" style="font-size: 1rem; line-height: 1;"></i>`;
            button.setAttribute('data-bs-toggle', 'dropdown');
            button.setAttribute('data-bs-auto-close', 'outside'); // Keep open if submenu is clicked
            button.setAttribute('aria-expanded', 'false');
            inlineAiFloatingButton.appendChild(button);

            const dropdownMenuEl = document.createElement('ul');
            dropdownMenuEl.className = 'dropdown-menu shadow-lg';
            // Populate menu items
            inlineAiActions.forEach(action => {
                const li = document.createElement('li');
                if (action.isSubmenu) {
                    li.className = 'dropdown-submenu';
                    const link = document.createElement('a');
                    link.className = 'dropdown-item dropdown-toggle';
                    link.href = '#';
                    link.setAttribute('role', 'button');
                    link.setAttribute('data-bs-toggle', 'dropdown'); // For Bootstrap 5 submenu
                    link.innerHTML = `<i class="${action.icon}"></i> ${action.label}`;
                    li.appendChild(link);

                    const subMenu = document.createElement('ul');
                    subMenu.className = 'dropdown-menu';
                    action.subActions.forEach(subAction => {
                        const subLi = document.createElement('li');
                        const subLink = document.createElement('a');
                        subLink.className = 'dropdown-item';
                        subLink.href = '#';
                        subLink.dataset.actionId = subAction.id;
                        subLink.dataset.actionSpecificParam = subAction.originalToneValue;
                        subLink.innerHTML = `<i class="${subAction.icon || 'bi-dash'}"></i> ${subAction.label}`;
                        subLi.appendChild(subLink);
                        subMenu.appendChild(subLi);
                    });
                    li.appendChild(subMenu);

                } else {
                    const link = document.createElement('a');
                    link.className = 'dropdown-item';
                    link.href = '#';
                    link.dataset.actionId = action.id;
                    link.innerHTML = `<i class="${action.icon}"></i> ${action.label}`;
                    li.appendChild(link);
                }
                dropdownMenuEl.appendChild(li);
            });

            inlineAiFloatingButton.appendChild(dropdownMenuEl);
            document.body.appendChild(inlineAiFloatingButton);
            inlineAiDropdown = new bootstrap.Dropdown(button); // Initialize on the button part

            // Event listener for menu item clicks
            dropdownMenuEl.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                const targetLink = event.target.closest('a.dropdown-item[data-action-id]');
                if (targetLink && currentInlineEditorInstance) {
                    const actionId = targetLink.dataset.actionId;
                    const actionSpecificParam = targetLink.dataset.actionSpecificParam; // e.g., tone value

                    let actionToExecute = inlineAiActions.find(a => a.id === actionId);
                    if (!actionToExecute && actionId.startsWith('rewriteTone_')) {
                        const parentAction = inlineAiActions.find(a => a.id === 'rewriteTone');
                        actionToExecute = parentAction?.subActions?.find(sa => sa.id === actionId);
                    }

                    if (actionToExecute) {
                        await handleInlineAIAction(currentInlineEditorInstance, actionToExecute, actionSpecificParam);
                    }
                    inlineAiDropdown.hide();
                    hideInlineAiButton();
                }
            });
        }
    }

    function updateInlineAiFloatingButtonPosition(editor) {
        if (!inlineAiFloatingButton || editor.model.document.selection.isCollapsed) {
            hideInlineAiButton();
            return;
        }

        const view = editor.editing.view;
        const viewSelection = view.document.selection;
        const firstRange = viewSelection.getFirstRange();

        if (!firstRange) {
            hideInlineAiButton();
            return;
        }

        try {
            // Get the DOM range from the view range
            const domRange = view.domConverter.viewRangeToDom(firstRange);
            if (!domRange) { hideInlineAiButton(); return; }

            const editableElement = view.domConverter.viewToDom(view.document.getRoot());
            if (!editableElement || !editableElement.contains(domRange.startContainer)) {
                 hideInlineAiButton(); return;
            }

            const rect = domRange.getBoundingClientRect();
            const editorRect = editableElement.getBoundingClientRect();

            // Position the button to the top-right of the selection, relative to the viewport
            let top = window.scrollY + rect.top - (inlineAiFloatingButton.offsetHeight);
            let left = window.scrollX + rect.right;

            // Adjust if out of editor bounds or too close to edge
            top = Math.max(top, window.scrollY + editorRect.top); // Don't go above editor
            left = Math.min(left, window.scrollX + editorRect.right - inlineAiFloatingButton.offsetWidth - 5); // Don't go past editor right
            left = Math.max(left, window.scrollX + editorRect.left + 5); // Don't go past editor left

            inlineAiFloatingButton.style.top = `${top - 5}px`; // Small offset above
            inlineAiFloatingButton.style.left = `${left}px`;
            inlineAiFloatingButton.classList.remove('d-none');
            inlineAiFloatingButton.style.opacity = '1';
            inlineAiFloatingButton.style.transform = 'scale(1)';

        } catch (error) {
            console.warn("Error calculating inline AI button position:", error);
            hideInlineAiButton();
        }
    }


    function showInlineAiButton(editor) {
        if (!aiEnabled || !inlineAiFloatingButton) return;
        currentInlineEditorInstance = editor;
        // Find the ID of the current editor based on its DOM element
        const editorElement = editor.ui.view.element;
        if (editorElement) {
             const wrapper = editorElement.closest('.ckeditor-wrapper-class');
             currentInlineEditorId = wrapper ? wrapper.dataset.targetValidationId : null;
        } else {
            currentInlineEditorId = null;
        }
        updateInlineAiFloatingButtonPosition(editor);
    }

    function hideInlineAiButton() {
        if (inlineAiFloatingButton) {
            inlineAiFloatingButton.classList.add('d-none');
            inlineAiFloatingButton.style.opacity = '0';
            inlineAiFloatingButton.style.transform = 'scale(0.8)';
            if(inlineAiDropdown && inlineAiDropdown._isShown) {
                inlineAiDropdown.hide();
            }
        }
        currentInlineEditorInstance = null;
        currentInlineEditorId = null;
    }

    async function handleInlineAIAction(editor, action, actionSpecificParam) {
        if (!editor || !action || !action.promptBuilderKey || !aiEnabled) return;

        const originalButtonContent = inlineAiFloatingButton.querySelector('button').innerHTML;
        inlineAiFloatingButton.querySelector('button').innerHTML = `<div class="spinner-border spinner-border-sm text-white" role="status" style="width: 1rem; height: 1rem;"></div>`;
        inlineAiDropdown.hide();

        try {
            const selection = editor.model.document.selection;
            if (selection.isCollapsed) {
                Swal.fire("Atenção", "Por favor, selecione um trecho de texto primeiro.", "info");
                return;
            }

            const selectedText = getPlainTextFromSelection(editor, selection);
            if (!selectedText.trim()) {
                Swal.fire("Atenção", "A seleção está vazia ou contém apenas espaços.", "info");
                return;
            }

            const context = buildFullContext(); // Reuse existing context builder
            const promptFn = inlineAiPrompts[action.promptBuilderKey];
            if (!promptFn) {
                console.error(`Construtor de prompt não encontrado para: ${action.promptBuilderKey}`);
                Swal.fire("Erro", "Ação de IA não configurada corretamente.", "error");
                return;
            }

            const promptText = promptFn(selectedText, context, 3, actionSpecificParam); // actionSpecificParam is tone for rewrite
            
            // Call a modified getGeminiSuggestions or a new specific function
            // For this example, let's assume getGeminiSuggestions can return the text
            const aiSuggestionText = await getGeminiSuggestions(promptText, action.id, null, null, null, 1, true); // True to return text

            if (!aiSuggestionText) {
                Swal.fire("Sem Sugestão", "A IA não retornou uma sugestão para esta ação.", "info");
                return;
            }

            if (action.insertionMode === 'showSuggestionsList') {
                const alternatives = aiSuggestionText.split('\n').map(s => s.trim()).filter(Boolean);
                if (alternatives.length > 0) {
                    const { value: chosenAlternative } = await Swal.fire({
                        title: 'Escolha uma Alternativa',
                        input: 'radio',
                        inputOptions: alternatives.reduce((obj, item) => {
                            obj[item] = item;
                            return obj;
                        }, {}),
                        inputValidator: (value) => !value && 'Você precisa escolher uma opção!',
                        confirmButtonText: 'Aplicar Selecionada',
                        showCancelButton: true,
                        cancelButtonText: 'Cancelar'
                    });

                    if (chosenAlternative) {
                        editor.model.change(writer => {
                            const currentSelectionRanges = Array.from(selection.getRanges());
                            currentSelectionRanges.forEach(range => {
                                writer.remove(range);
                                writer.insertText(chosenAlternative, range.start);
                            });
                        });
                        Swal.fire('Aplicado!', 'Alternativa aplicada ao texto.', 'success');
                    }
                } else {
                     Swal.fire("Sem Alternativas", "A IA não forneceu alternativas válidas.", "info");
                }
            } else {
                editor.model.change(writer => {
                    const currentSelectionRanges = Array.from(selection.getRanges());
                    currentSelectionRanges.forEach(range => {
                        if (action.insertionMode === 'replace') {
                            writer.remove(range);
                            writer.insertText(aiSuggestionText, range.start);
                        } else if (action.insertionMode === 'afterOrReplace') {
                            // If selection is a full paragraph, replace. Otherwise, append.
                            const selectedElement = selection.getSelectedElement();
                            if (selectedElement && editor.model.schema.isBlock(selectedElement)) {
                                writer.remove(range);
                                writer.insertText(aiSuggestionText, range.start);
                            } else {
                                writer.insertText(" " + aiSuggestionText, range.end); // Add space before appending
                            }
                        } else if (action.insertionMode === 'after') {
                             writer.insertText(" " + aiSuggestionText, range.end);
                        } else if (action.insertionMode === 'before') {
                             writer.insertText(aiSuggestionText + " ", range.start);
                        }
                        // More modes can be added
                    });
                });
                // Simple visual feedback in editor
                const editorUIView = editor.editing.view.document.getRoot();
                if(editorUIView) {
                    const domElem = editor.editing.view.domConverter.mapViewToDom(editorUIView);
                    if(domElem) {
                        domElem.classList.add('ai-content-updated-flash');
                        setTimeout(() => domElem.classList.remove('ai-content-updated-flash'), 1000);
                    }
                }
            }

        } catch (error) {
            console.error("Erro durante ação de IA inline:", error);
            Swal.fire("Erro na IA", `Ocorreu um erro: ${error.message}`, "error");
        } finally {
            inlineAiFloatingButton.querySelector('button').innerHTML = `<i class="bi bi-magic" style="font-size: 1rem; line-height: 1;"></i>`; // Restore icon
            hideInlineAiButton(); // Hide after action
        }
    }

    function getPlainTextFromSelection(editor, selection) {
        const fragment = editor.model.getSelectedContent(selection);
        let plainText = '';
        for (const item of fragment.getChildren()) {
            if (item.is('$text') || item.is('$textProxy')) {
                plainText += item.data;
            } else if (item.is('element') && item.name === 'paragraph') { // Handle paragraphs
                for (const child of item.getChildren()) {
                    if (child.is('$text') || child.is('$textProxy')) {
                        plainText += child.data;
                    }
                }
                plainText += '\n'; // Add newline for paragraphs
            }
            // Could add more complex handling for other element types if needed
        }
        return plainText.trim();
    }


    // --- Helper to strip HTML (for text/md reports from CKEditor) ---
    function stripHtml(html) {
        if (!html) return "";
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    // --- Persistence Functions ---
    function saveState(showStatus = false) {
        Object.keys(ckEditorInstances).forEach(editorId => {
            if (ckEditorInstances[editorId] && document.getElementById(editorId)) {
                collectedFormData[editorId] = ckEditorInstances[editorId].getData();
            }
        });

        try {
            const state = {
                currentStep: currentStep,
                formData: collectedFormData
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            if (showStatus) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Progresso salvo!',
                    html: `Salvo às ${new Date().toLocaleTimeString()}`,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });
            }
        } catch (error) {
            console.error("Erro ao salvar estado:", error);
             Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Erro ao salvar progresso.',
                showConfirmButton: false,
                timer: 3000
            });
        }
         clearTimeout(autoSaveTimer);
    }

    function loadState() {
        try {
            const savedStateJSON = localStorage.getItem(STORAGE_KEY);
            if (savedStateJSON) {
                const savedState = JSON.parse(savedStateJSON);
                if (savedState && typeof savedState.currentStep === 'number' && savedState.formData) {
                    currentStep = savedState.currentStep;
                    collectedFormData = savedState.formData;
                    return true;
                }
            }
        } catch (error) {
            console.error("Erro ao carregar estado:", error);
            localStorage.removeItem(STORAGE_KEY);
        }
        return false;
    }

    function clearState(fromTemplateSelection = false) {
        const doClear = () => {
            localStorage.removeItem(STORAGE_KEY);
            currentStep = 0;
            collectedFormData = {};
            Object.keys(ckEditorInstances).forEach(id => {
                if (ckEditorInstances[id]) ckEditorInstances[id].setData('');
            });
             if (!fromTemplateSelection) {
                Swal.fire({
                    icon: 'info',
                    title: 'Plano Limpo',
                    text: 'Todos os dados foram removidos.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            renderStep(0);
            hideCompletionSection();
        };

        if (fromTemplateSelection) {
            doClear();
        } else {
            Swal.fire({
                title: 'Limpar Plano?',
                text: "Tem certeza que deseja limpar todo o plano? Esta ação não pode ser desfeita.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, limpar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    doClear();
                }
            });
        }
    }

    // --- Auto-Save Logic ---
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            collectStepData();
            saveState(false); // Don't show toast for auto-save
        }, AUTO_SAVE_INTERVAL);
    }

    stepsContainer.addEventListener('input', scheduleAutoSave);
    stepsContainer.addEventListener('change', scheduleAutoSave);


    // --- Validation Function ---
    function validateStep(stepIndex) {
        const stepElement = stepsContainer.querySelector(`.wizard-step[data-step-index="${stepIndex}"]`);
        if (!stepElement) return true;

        let isValid = true;
        validationErrorEl.style.display = 'none';
        const requiredQuestions = steps[stepIndex].questions.filter(q => q.required);

        stepElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        stepElement.querySelectorAll('.is-invalid-ckeditor').forEach(el => el.classList.remove('is-invalid-ckeditor'));


        requiredQuestions.forEach(qData => {
            let fieldValid = false;
            if (qData.type === 'radio') {
                const radios = stepElement.querySelectorAll(`input[name="${qData.id}"]`);
                const isOtherChecked = stepElement.querySelector(`input[name="${qData.id}"][value="other"]`)?.checked;
                const otherText = stepElement.querySelector(`#${qData.id}_other_text`)?.value.trim();
                if (isOtherChecked) fieldValid = !!otherText;
                else fieldValid = Array.from(radios).some(radio => radio.checked && radio.value !== 'other');

                if (!fieldValid) {
                     radios.forEach(r => r.closest('.form-check')?.classList.add('is-invalid'));
                     const otherInputEl = stepElement.querySelector(`#${qData.id}_other_text`);
                     if(otherInputEl) otherInputEl.classList.add('is-invalid');
                } else {
                    radios.forEach(r => r.closest('.form-check')?.classList.remove('is-invalid'));
                    const otherInputEl = stepElement.querySelector(`#${qData.id}_other_text`);
                    if(otherInputEl) otherInputEl.classList.remove('is-invalid');
                }
            } else if (qData.type === 'checkbox') {
                 const checkboxes = stepElement.querySelectorAll(`input[name^="${qData.id}_"]`);
                 const isOtherChecked = stepElement.querySelector(`input[name="${qData.id}_other"]`)?.checked;
                 const otherText = stepElement.querySelector(`#${qData.id}_other_text`)?.value.trim();
                 let standardChecked = Array.from(checkboxes).some(cb => cb.checked && !cb.classList.contains('other-option-trigger'));

                 if (isOtherChecked) fieldValid = !!otherText;
                 else fieldValid = standardChecked;

                 if (!fieldValid) {
                    checkboxes.forEach(cb => cb.closest('.form-check')?.classList.add('is-invalid'));
                    const otherInputEl = stepElement.querySelector(`#${qData.id}_other_text`);
                    if(otherInputEl) otherInputEl.classList.add('is-invalid');
                 } else {
                    checkboxes.forEach(cb => cb.closest('.form-check')?.classList.remove('is-invalid'));
                    const otherInputEl = stepElement.querySelector(`#${qData.id}_other_text`);
                    if(otherInputEl) otherInputEl.classList.remove('is-invalid');
                 }
            } else if (qData.type === 'textarea' && ckEditorFieldIds.includes(qData.id)) {
                if (ckEditorInstances[qData.id]) {
                    const editorData = ckEditorInstances[qData.id].getData();
                    fieldValid = editorData.trim() !== '' && editorData.trim() !== '<p></p>';
                    const editorElement = ckEditorInstances[qData.id].ui.view.element;
                    if (editorElement) {
                        const wrapper = editorElement.closest('.ckeditor-wrapper-class');
                        if (!fieldValid) wrapper?.classList.add('is-invalid-ckeditor');
                        else wrapper?.classList.remove('is-invalid-ckeditor');
                    }
                } else { fieldValid = false; }
            } else {
                const inputElement = stepElement.querySelector(`#${qData.id}`);
                if (inputElement && inputElement.value.trim() === '') {
                    fieldValid = false;
                    inputElement.classList.add('is-invalid');
                } else if (inputElement) {
                     fieldValid = true;
                     inputElement.classList.remove('is-invalid');
                 } else { fieldValid = true; }
            }
            if (!fieldValid) isValid = false;
        });

        if (!isValid) {
            validationErrorEl.textContent = 'Por favor, preencha todos os campos obrigatórios marcados.';
            validationErrorEl.style.display = 'block';
            const firstInvalid = stepElement.querySelector('.is-invalid, .is-invalid-ckeditor .ck.ck-editor__main > .ck-editor__editable');
            if (firstInvalid) {
                 if (firstInvalid.classList.contains('ck-editor__editable')) {
                    const ckWrapper = firstInvalid.closest('.ckeditor-wrapper-class');
                    const editorId = ckWrapper?.dataset.targetValidationId;
                    if (editorId && ckEditorInstances[editorId]) {
                         ckEditorInstances[editorId].editing.view.focus();
                    }
                 } else {
                    firstInvalid.focus();
                 }
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        return isValid;
    }

     // --- Loading State Functions ---
     function showLoading(show = true) {
         if (show) {
             loadingOverlay.style.display = 'flex';
             prevBtn.disabled = true;
             nextBtn.disabled = true;
         } else {
             loadingOverlay.style.display = 'none';
             prevBtn.disabled = currentStep === 0;
             nextBtn.disabled = false;
         }
     }

    // --- AI Functions ---
    function updateAPIKeyStatusUI() {
        const aiAssistanceTriggerBtn = document.querySelector('[data-bs-target="#aiAssistanceModal"]');
        if (aiEnabled) {
            apiKeyStatusContainer.innerHTML = `✨ Funcionalidades de IA (Gemini) ATIVADAS.`;
            apiKeyStatusContainer.className = 'container-fluid api-ok';
            if (aiAssistanceTriggerBtn) aiAssistanceTriggerBtn.disabled = false;
        } else {
            apiKeyStatusContainer.innerHTML = `🔑 Funcionalidades de IA (Gemini) desativadas.
                <button type="button" class="btn btn-sm btn-warning ms-2" id="configureApiKeyBtn">Configurar API Key</button>
                <small class="d-block mt-1">Obtenha sua API Key em <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">Google AI Studio</a>.</small>`;
            apiKeyStatusContainer.className = 'container-fluid';
            document.getElementById('configureApiKeyBtn')?.addEventListener('click', () => promptForAPIKey(true));
            if (aiAssistanceTriggerBtn) aiAssistanceTriggerBtn.disabled = true;
        }
    }

    function initializeAI() {
        if (geminiAPIKey) {
            try {
                genAI = new GoogleGenerativeAI(geminiAPIKey);
                aiModel = genAI.getGenerativeModel({ model: GEMINI_MODEL_NAME });
                aiEnabled = true; return true;
            } catch (error) {
                console.error("Erro ao inicializar Gemini AI SDK:", error);
                localStorage.removeItem('geminiAPIKey');
                geminiAPIKey = ''; aiEnabled = false; return false;
            }
        }
        aiEnabled = false; return false;
    }

    async function promptForAPIKey(forceRerender = false) {
        const { value: key } = await Swal.fire({
            title: 'Configurar Google AI Gemini API Key',
            input: 'text',
            inputValue: localStorage.getItem('geminiAPIKey') || '',
            inputPlaceholder: 'Cole sua API Key aqui',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Salvar Key',
            inputValidator: (value) => {
                if (!value || value.trim() === '') {
                    return 'Por favor, insira uma API Key válida!'
                }
            }
        });

        if (key && key.trim() !== '') {
            geminiAPIKey = key.trim();
            localStorage.setItem('geminiAPIKey', geminiAPIKey);
        } else if (key === undefined && localStorage.getItem('geminiAPIKey')) { 
             geminiAPIKey = localStorage.getItem('geminiAPIKey');
        } else if (key !== null) { 
            localStorage.removeItem('geminiAPIKey');
            geminiAPIKey = ''; aiEnabled = false;
        }

        initializeAI();
        updateAPIKeyStatusUI();
        if (forceRerender) renderStep(currentStep);
    }

    async function getGeminiSuggestions(promptText, suggestionType, targetElementId, buttonElement, directTargetFieldId = null, suggestionCount = 3, returnRawText = false) {
        if (!aiEnabled || !aiModel) {
            if(!returnRawText) { // Don't show Swal if we just want the raw text for inline AI
                Swal.fire({
                    icon: 'warning',
                    title: 'IA Não Configurada',
                    text: 'As funcionalidades de IA não estão habilitadas. Por favor, verifique sua API Key.',
                    confirmButtonText: 'Configurar API Key'
                }).then((result) => {
                    if (result.isConfirmed) {
                        promptForAPIKey(true);
                    }
                });
                updateAPIKeyStatusUI(); 
            }
            return returnRawText ? null : undefined;
        }
        const targetEl = targetElementId ? document.getElementById(targetElementId) : null;
        let originalButtonHtml = '';

        if (buttonElement) { // For non-inline AI calls
            originalButtonHtml = buttonElement.innerHTML;
            buttonElement.classList.add('loading');
            buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            buttonElement.disabled = true;
        }
        if (!returnRawText) showLoading(true);

        if (targetEl) { // For regular AI suggestions in dedicated divs
             targetEl.style.display = 'block';
             targetEl.innerHTML = '<p class="text-center my-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando sugestões...</p>';
        }
        
        try {
            if (promptText.includes("[N_SUGESTOES]")) promptText = promptText.replace(/\[N_SUGESTOES\]/g, suggestionCount);
            if (promptText.includes("[N_TITULOS]")) promptText = promptText.replace(/\[N_TITULOS\]/g, suggestionCount);
            if (promptText.includes("[N_CAPITULOS]")) promptText = promptText.replace(/\[N_CAPITULOS\]/g, suggestionCount);
            if (promptText.includes("[N_SUBTOPICOS]")) promptText = promptText.replace(/\[N_SUBTOPICOS\]/g, suggestionCount > 0 ? suggestionCount : 3);

            const generationConfig = { temperature: 0.7, topK: 1, topP: 1, maxOutputTokens: 8192 };
            const safetySettings = [
                { category: HarmCategory.HARM_CATEGORY_HARASSMENT, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
                { category: HarmCategory.HARM_CATEGORY_HATE_SPEECH, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
                { category: HarmCategory.HARM_CATEGORY_SEXUALLY_EXPLICIT, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
                { category: HarmCategory.HARM_CATEGORY_DANGEROUS_CONTENT, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
            ];
            const result = await aiModel.generateContent({ contents: [{ role: "user", parts: [{text: promptText}] }], generationConfig, safetySettings });
            const response = result.response;

            if (response.promptFeedback && response.promptFeedback.blockReason) {
                 let blockDetails = response.promptFeedback.safetyRatings?.filter(r => r.blocked).map(r => `${r.category}: ${r.probability}`).join(', ') || 'N/A';
                 throw new Error(`Conteúdo bloqueado pela API: ${response.promptFeedback.blockReason}. Detalhes: ${blockDetails}`);
            }
            if (!response.candidates?.[0]?.content?.parts?.[0]?.text) {
                 if(response.candidates?.[0]?.finishReason && response.candidates[0].finishReason !== 'STOP') {
                     throw new Error(`Geração interrompida pela API: ${response.candidates[0].finishReason}. Detalhes: ${JSON.stringify(response.candidates[0].safetyRatings)}`);
                 } else { throw new Error("Resposta da IA vazia ou em formato inesperado."); }
            }
            const suggestionsText = response.candidates[0].content.parts[0].text;

            if (returnRawText) {
                return suggestionsText;
            }

            if (directTargetFieldId) {
                renderSuggestions(null, suggestionsText, suggestionType, directTargetFieldId, buttonElement.parentElement);
            } else if (targetEl) {
                renderSuggestions(targetEl, suggestionsText, suggestionType);
            } else { // For AI Assistance Modal
                currentAiModalOutput = suggestionsText;
            }

        } catch (error) {
            console.error(`Erro ao chamar Gemini API para ${suggestionType}:`, error);
            let errorMessage = `Ocorreu um erro ao buscar sugestões da IA: ${error.message || 'Erro desconhecido'}`;
            let errorTitle = 'Erro na IA';
            let errorHtml = false;

             if (error.message?.includes("API key not valid") || error.message?.includes("API_KEY_INVALID")) {
                 errorMessage = `Sua API Key é inválida ou expirou. Verifique no <a href='https://aistudio.google.com/app/apikey' target='_blank' rel='noopener noreferrer'>Google AI Studio</a> e configure novamente.`;
                 errorHtml = true;
                 localStorage.removeItem('geminiAPIKey'); geminiAPIKey = ''; aiEnabled = false;
                 updateAPIKeyStatusUI(); renderStep(currentStep);
            } else if (error.message?.toLowerCase().includes("quota") || error.message?.includes("429")) {
                errorMessage = "Você excedeu sua cota da API ou está fazendo muitas requisições. Tente novamente mais tarde.";
            } else if (error.message?.includes("Content blocked") || (error.message?.includes("interrompida pela API") && error.message?.toLowerCase().includes("safety"))) {
                errorMessage = `A IA bloqueou a resposta por questões de segurança: ${error.message}. Tente reformular sua solicitação ou verifique as políticas de uso.`;
                errorTitle = 'Conteúdo Bloqueado';
            } else if (error.message?.includes("interrompida pela API")) {
                 errorMessage = `A geração foi interrompida pela API (${error.message.split(': ')[1]}). Verifique as políticas de segurança ou tente novamente.`;
                 errorTitle = 'Geração Interrompida';
            }

            if (returnRawText) {
                // For inline AI, we might want to throw the error so handleInlineAIAction can catch it
                throw error;
            }

            if (targetEl) targetEl.innerHTML = `<div class="alert alert-danger p-2 mt-2" role="alert">${errorHtml ? errorMessage : escapeHtml(errorMessage)}</div>`;
            else if (directTargetFieldId && buttonElement) {
                const feedbackContainer = buttonElement.parentElement || document.body;
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger p-2 mt-2';
                errorDiv.innerHTML = errorHtml ? errorMessage : escapeHtml(errorMessage);
                feedbackContainer.appendChild(errorDiv);
                setTimeout(() => errorDiv.remove(), 7000);
            }
            else if (aiAssistanceOutputEl && aiAssistanceModalEl.classList.contains('show')) {
                aiAssistanceOutputEl.innerHTML = `<div class="alert alert-danger p-3"><strong>${errorTitle}:</strong><br>${errorHtml ? errorMessage : escapeHtml(errorMessage)}</div>`;
            }
            else Swal.fire({ icon: 'error', title: errorTitle, html: errorHtml ? errorMessage : escapeHtml(errorMessage) });
             return returnRawText ? null : undefined;
        } finally {
            if (buttonElement) {
                buttonElement.classList.remove('loading');
                buttonElement.innerHTML = originalButtonHtml;
                buttonElement.disabled = false;
            }
            if(!returnRawText) showLoading(false);
        }
    }

    function renderSuggestions(container, text, type, directTargetFieldId = null, feedbackAnchorElement = null) {
        if (container) container.innerHTML = '';
        const lines = text.split('\n').map(line => line.trim()).filter(line => line);

        const createSuggestionItem = (contentHtml, useButtonConfig) => {
            if (!container) return;
            const itemDiv = document.createElement('div');
            itemDiv.className = 'ai-suggestion-item';
            itemDiv.innerHTML = contentHtml;
            if (useButtonConfig) {
                const useButton = document.createElement('button');
                useButton.type = 'button';
                useButton.className = 'btn btn-sm btn-outline-primary btn-use-suggestion mt-2';
                useButton.innerHTML = useButtonConfig.text || '✔️ Usar';
                useButton.onclick = (e) => {
                    useButtonConfig.action();
                    const feedback = document.createElement('span');
                    feedback.className = 'applied-feedback'; feedback.textContent = 'Aplicado!';
                    itemDiv.appendChild(feedback);
                    setTimeout(() => feedback.remove(), 2000);
                     e.stopPropagation();
                };
                itemDiv.appendChild(useButton);
            }
            container.appendChild(itemDiv);
        };

        const showAppliedFeedback = (anchorElement) => {
            if (!anchorElement) anchorElement = document.body;
            const feedback = document.createElement('span');
            feedback.className = 'applied-feedback';
            feedback.textContent = 'Aplicado!';
            feedback.style.position = 'relative';
            feedback.style.marginLeft = '10px';
            anchorElement.appendChild(feedback);
            setTimeout(() => feedback.remove(), 2000);
        };

        try {
            switch (type) {
                case 'titles':
                    try {
                        const suggestions = JSON.parse(text.trim());
                        if (!Array.isArray(suggestions)) throw new Error("Formato JSON inválido para títulos.");
                        if (suggestions.length === 0 && container) {
                             container.innerHTML = '<p class="text-muted">A IA não retornou sugestões de títulos.</p>';
                             break;
                        }
                        suggestions.forEach(sugg => {
                            if (!sugg.title) return;
                            createSuggestionItem(
                                `<strong>Título:</strong> <p>${escapeHtml(sugg.title)}</p>${sugg.subtitle ? `<strong>Subtítulo:</strong> <p>${escapeHtml(sugg.subtitle)}</p>` : ''}`,
                                {
                                    text: '✔️ Usar este',
                                    action: () => {
                                        document.getElementById('step3_q0').value = sugg.title || "";
                                        if (sugg.subtitle !== undefined) document.getElementById('step3_q1').value = sugg.subtitle || ""; 
                                        scheduleAutoSave();
                                    }
                                }
                            );
                        });
                    } catch (e) {
                        console.error("Erro ao analisar JSON de títulos:", e, "Texto recebido:", text);
                        if (container) {
                            container.innerHTML = '<p class="text-muted">Erro ao processar sugestões de títulos (esperava JSON). Exibindo resposta bruta:</p>';
                            createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null);
                        }
                    }
                    break;
                 case 'chapters':
                    try {
                        const cleanedText = text.replace(/^```json\s*|\s*```$/g, '').trim();
                        const chapterSuggestions = JSON.parse(cleanedText);

                        if (!Array.isArray(chapterSuggestions) || chapterSuggestions.length === 0) {
                            throw new Error("Formato JSON inválido ou vazio para capítulos.");
                        }

                        const fullStructure = chapterSuggestions.map(chap => {
                            let chapStr = `${chap.name || chap.title || 'Capítulo sem nome'}`;
                            if (chap.subtopics && Array.isArray(chap.subtopics) && chap.subtopics.length > 0) {
                                chapStr += `\n${chap.subtopics.map(s => `  - ${s}`).join('\n')}`;
                            }
                            return chapStr;
                        }).join('\n\n');

                        if (container) {
                             createSuggestionItem(
                                 `<pre>${escapeHtml(fullStructure)}</pre>`,
                                 { text: '✔️ Usar esta Estrutura', action: () => {
                                      document.getElementById('step4_q0').value = chapterSuggestions.map(c => c.name || c.title).join('\n');
                                      const subtopicsForEditor = chapterSuggestions
                                        .map(chap => {
                                            let editorContent = `<h2>${escapeHtml(chap.name || chap.title)}</h2>`;
                                            if (chap.subtopics && chap.subtopics.length > 0) {
                                                editorContent += `<ul>${chap.subtopics.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`;
                                            }
                                            return editorContent;
                                        })
                                        .join('<p>&nbsp;</p>');
                                      if (ckEditorInstances['step4_q1'] && subtopicsForEditor) {
                                          ckEditorInstances['step4_q1'].setData(subtopicsForEditor);
                                      } else if (document.getElementById('step4_q1') && subtopicsForEditor) {
                                          let plainTextSubtopics = chapterSuggestions.map(chap => {
                                            let plain = chap.name || chap.title;
                                            if (chap.subtopics && chap.subtopics.length > 0) {
                                                plain += '\n' + chap.subtopics.map(s => `  - ${s}`).join('\n');
                                            }
                                            return plain;
                                          }).join('\n\n');
                                          document.getElementById('step4_q1').value = plainTextSubtopics;
                                      }
                                      scheduleAutoSave();
                                  }}
                             );
                        }
                    } catch (e) {
                         console.error("Erro ao analisar JSON de capítulos ou fallback para análise de texto:", e, "Texto recebido:", text);
                         const parsedFallback = parseChapterSuggestions(text); 
                         if (parsedFallback.length > 0 && container) {
                            container.innerHTML = '<p class="text-muted">Falha ao processar JSON, usando análise de texto:</p>';
                            const fullStructure = parsedFallback.map(chap => `${chap.name}\n${chap.subtopics.map(s => `  - ${s}`).join('\n')}`).join('\n\n');
                            createSuggestionItem(
                                `<pre>${escapeHtml(fullStructure)}</pre>`,
                                { text: '✔️ Usar Estrutura (Texto)', action: () => {
                                    document.getElementById('step4_q0').value = parsedFallback.map(c => c.name).join('\n');
                                    const subtopicsForEditor = parsedFallback
                                        .map(chap => `<h2>${escapeHtml(chap.name)}</h2><ul>${chap.subtopics.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`)
                                        .join('<p>&nbsp;</p>');
                                    if (ckEditorInstances['step4_q1']) ckEditorInstances['step4_q1'].setData(subtopicsForEditor);
                                    scheduleAutoSave();
                                }}
                            );
                         } else if (container) {
                             container.innerHTML = '<p class="text-muted">Nenhuma sugestão de capítulo válida (JSON ou texto). Exibindo resposta bruta:</p>';
                             createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null);
                         }
                    }
                    break;
                case 'subtopicsFromChapters': 
                    if (directTargetFieldId && ckEditorInstances[directTargetFieldId]) {
                        const htmlContent = text.split('\n').map(line => {
                            const trimmedLine = line.trim();
                            if (!trimmedLine) return "";
                            const mainChaptersList = (collectedFormData['step4_q0'] || "").split('\n').map(c => c.trim().toLowerCase()).filter(Boolean);
                            if (trimmedLine.match(/^(?:Cap[íi]tulo|Seção|Parte)\s*\d*[:\.]?/i) || mainChaptersList.includes(trimmedLine.toLowerCase())) {
                                return `<h2>${escapeHtml(trimmedLine)}</h2>`;
                            } else if (trimmedLine.match(/^\s*[-*\u2022•]\s+/)) { 
                                return `<ul><li>${escapeHtml(trimmedLine.replace(/^\s*[-*\u2022•]\s+/, ''))}</li></ul>`;
                            } else if (trimmedLine.match(/^\s*\w\)|\s*\d+\.\d+\.?\s+/)) { 
                                return `<ul><li>${escapeHtml(trimmedLine.replace(/^\s*(\w\)|\d+\.\d+\.?)\s*/, '$1 '))}</li></ul>`;
                            }
                            return `<p>${escapeHtml(trimmedLine)}</p>`;
                        }).join('').replace(/<\/ul><h2>/g, '</ul><p>&nbsp;</p><h2>').replace(/<\/ul><ul>/g, '').replace(/<\/li><\/ul><ul><li>/g, '</li><li>');


                        ckEditorInstances[directTargetFieldId].setData(htmlContent);
                        scheduleAutoSave();
                        if(feedbackAnchorElement) showAppliedFeedback(feedbackAnchorElement);
                    } else if (directTargetFieldId) {
                        document.getElementById(directTargetFieldId).value = text;
                        scheduleAutoSave();
                        if(feedbackAnchorElement) showAppliedFeedback(feedbackAnchorElement);
                    }
                    break;
                case 'persona':
                    if (container) {
                        createSuggestionItem( 
                            `<pre>${escapeHtml(text)}</pre>`,
                            { text: '📋 Usar como base para Persona', action: () => {
                                const targetEditorId = 'step1_q0';
                                if (ckEditorInstances[targetEditorId]) {
                                    ckEditorInstances[targetEditorId].setData(text.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>'));
                                } else {
                                    document.getElementById(targetEditorId).value = text;
                                }
                                scheduleAutoSave();
                            }}
                        );
                    }
                    break;
                case 'elevatorPitch': case 'problem': case 'painPoint': case 'outcome':
                case 'writingStyle': case 'coverConcept': case 'launchAction':
                     const targetInputIdMap = {
                         elevatorPitch: 'step3_q2', problem: 'step0_q1', painPoint: 'step1_q2',
                         outcome: 'step2_q0', writingStyle: 'step6_q1', coverConcept: 'step9_q0',
                         launchAction: 'step10_q2'
                     };
                     const targetInputId = targetInputIdMap[type];
                     if (!targetInputId || !container) { if (container) createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null); break; }

                    lines.forEach((line) => {
                        const cleanedLine = line.replace(/^[\d\.\-\*\s]+/, '').trim();
                        if(cleanedLine){
                             createSuggestionItem(
                                 `<p>${escapeHtml(cleanedLine)}</p>`,
                                 { text: '✔️ Usar este', action: () => {
                                      if (ckEditorInstances[targetInputId]) {
                                          ckEditorInstances[targetInputId].setData(cleanedLine.replace(/\n/g, '<br>'));
                                      } else {
                                          document.getElementById(targetInputId).value = cleanedLine;
                                      }
                                      scheduleAutoSave();
                                  }}
                             );
                        }
                    });
                     if (container.childElementCount === 0 && lines.length > 0) { 
                         createSuggestionItem(
                             `<pre>${escapeHtml(text)}</pre>`,
                             { text: '✔️ Usar este texto', action: () => {
                                  if (ckEditorInstances[targetInputId]) {
                                      ckEditorInstances[targetInputId].setData(text.replace(/\n/g, '<br>'));
                                  } else {
                                      document.getElementById(targetInputId).value = text;
                                  }
                                  scheduleAutoSave();
                              }}
                         );
                     } else if (container.childElementCount === 0 && container) {
                         container.innerHTML = '<p class="text-muted">Nenhuma sugestão individualizada. Exibindo resposta bruta:</p>';
                         createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null);
                     }
                    break;
                 case 'extraElements': case 'marketingChannels':
                     const targetCheckboxPrefixMap = { extraElements: 'step4_q2', marketingChannels: 'step10_q1' };
                     const checkboxPrefix = targetCheckboxPrefixMap[type];
                     if(!checkboxPrefix || !container) { if (container) createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null); break; }
                    const listItems = lines.map(line => line.replace(/^[\d\.\-\*\s]+/, '').trim()).filter(Boolean);
                    if(listItems.length > 0){
                         createSuggestionItem(
                             `<ul>${listItems.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`,
                             { text: '✔️ Marcar Opções Sugeridas', action: () => {
                                 const stepElement = document.querySelector(`[data-step-index="${currentStep}"]`);
                                 listItems.forEach(suggestedItemText => {
                                     const labels = stepElement.querySelectorAll(`input[name^="${checkboxPrefix}_"] + label`);
                                     let foundMatch = false;
                                     labels.forEach(label => {
                                         const normalizedLabel = label.textContent.toLowerCase().trim();
                                         const normalizedSuggestion = suggestedItemText.toLowerCase().trim();
                                         if (normalizedLabel.includes(normalizedSuggestion) || normalizedSuggestion.includes(normalizedLabel.split('(')[0].trim()) ) {
                                             const input = document.getElementById(label.htmlFor);
                                             if (input && input.type === 'checkbox') { input.checked = true; input.dispatchEvent(new Event('change', { bubbles: true })); foundMatch = true;}
                                         }
                                     });
                                     if (!foundMatch) {
                                         const otherCheckbox = stepElement.querySelector(`#${checkboxPrefix}_other_trigger`);
                                         const otherText = stepElement.querySelector(`#${checkboxPrefix}_other_text`);
                                         if(otherCheckbox && otherText){
                                             if(!otherCheckbox.checked) {
                                                otherCheckbox.checked = true; otherCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                                                otherText.value = suggestedItemText;
                                             } else if (otherText.value && !otherText.value.toLowerCase().includes(suggestedItemText.toLowerCase())) {
                                                otherText.value += `, ${suggestedItemText}`;
                                             } else if (!otherText.value) {
                                                otherText.value = suggestedItemText;
                                             }
                                         }
                                     }
                                 });
                                 scheduleAutoSave();
                             }}
                         );
                    } else if (container) {
                         container.innerHTML = '<p class="text-muted">Nenhuma sugestão válida. Exibindo resposta bruta:</p>';
                         createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null);
                     }
                    break;
                default:
                    console.warn("Tipo de sugestão não tratado:", type);
                    if (container) createSuggestionItem(`<pre>${escapeHtml(text)}</pre>`, null);
            }
        } catch (error) {
            console.error("Erro ao renderizar sugestões:", error, "Texto recebido:", text);
            if (container) container.innerHTML = `<div class="alert alert-warning p-2 mt-2" role="alert">Erro ao processar. Exibindo resposta bruta:</div><pre>${escapeHtml(text)}</pre>`;
        }
    }

    function parseChapterSuggestions(text) { 
        const suggestions = []; let currentChapter = null;
        text.split('\n').forEach(line => {
            const trimmedLine = line.trim();
            const chapterMatch = trimmedLine.match(/^(?:Cap[íi]tulo|Seção|Parte|Cap|Section)\s*\d*[:\.]?\s*(.*)/i);
            const subtopicMatch = trimmedLine.match(/^\s*(?:[-*\u2022•]|\w\)| \d+\.\d+\.?)\s+(.*)/); 

            if (chapterMatch && chapterMatch[1].trim() && !subtopicMatch && chapterMatch[1].length > 3) {
                 if (currentChapter && currentChapter.name) suggestions.push(currentChapter);
                 currentChapter = { name: chapterMatch[1].trim(), subtopics: [] };
            } else if (subtopicMatch && currentChapter) {
                 const subtopicName = subtopicMatch[1].trim();
                 if(subtopicName) currentChapter.subtopics.push(subtopicName);
            } else if (trimmedLine && !subtopicMatch && trimmedLine.length > 5 && /^[A-ZÀ-ÖØ-Þ]/.test(trimmedLine) && (!currentChapter || suggestions.length === 0 || (currentChapter && currentChapter.subtopics.length > 0))) {
                if (currentChapter && currentChapter.name) suggestions.push(currentChapter);
                currentChapter = { name: trimmedLine, subtopics: [] };
            }
        });
        if (currentChapter && currentChapter.name) suggestions.push(currentChapter);
        return suggestions;
    }


    // --- TEMPLATE Functions ---
    function populateTemplateDropdown() {
        if (!templateDropdownMenu) return;
        templateDropdownMenu.innerHTML = ''; 

        let li = document.createElement('li');
        let a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.textContent = '-- Começar do Zero --';
        a.dataset.templateKey = ''; 
        li.appendChild(a);
        templateDropdownMenu.appendChild(li);

        templateDropdownMenu.appendChild(document.createElement('hr'));

        for (const categoryKey in ebookTemplates) {
            const category = ebookTemplates[categoryKey];
            li = document.createElement('li');
            let header = document.createElement('span');
            header.className = 'dropdown-header';
            header.textContent = category.name;
            li.appendChild(header);
            templateDropdownMenu.appendChild(li);

            if (category.subcategories) {
                for (const subcatKey in category.subcategories) {
                    const subcategory = category.subcategories[subcatKey];
                    li = document.createElement('li');
                    let subHeader = document.createElement('span'); 
                    subHeader.className = 'dropdown-item subcategory-header'; 
                    subHeader.textContent = subcategory.name;
                    li.appendChild(subHeader);
                    templateDropdownMenu.appendChild(li);

                    for (const templateKey in subcategory.templates) {
                        const template = subcategory.templates[templateKey];
                        li = document.createElement('li');
                        a = document.createElement('a');
                        a.className = 'dropdown-item template-item';
                        a.href = '#';
                        a.textContent = template.name;
                        a.dataset.templateKey = `${categoryKey}.${subcatKey}.${templateKey}`; 
                        li.appendChild(a);
                        templateDropdownMenu.appendChild(li);
                    }
                }
            }

            if (category.templates) { 
                 for (const templateKey in category.templates) {
                    const template = category.templates[templateKey];
                    li = document.createElement('li');
                    a = document.createElement('a');
                    a.className = 'dropdown-item template-item'; 
                    a.href = '#';
                    a.textContent = template.name;
                    a.dataset.templateKey = `${categoryKey}.${templateKey}`; 
                    li.appendChild(a);
                    templateDropdownMenu.appendChild(li);
                }
            }
        }
    }

    function getTemplateDataByKey(fullKey) {
        if (!fullKey) return null;
        const parts = fullKey.split('.');
        let currentLevel = ebookTemplates;
        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            if (currentLevel[part]) {
                if (i === parts.length - 1) { 
                    return currentLevel[part];
                }
                if (currentLevel[part].subcategories && currentLevel[part].subcategories[parts[i+1]]) {
                    currentLevel = currentLevel[part].subcategories;
                } else if (currentLevel[part].templates && currentLevel[part].templates[parts[i+1]]) {
                    currentLevel = currentLevel[part].templates;
                } else if (i === parts.length - 2 && currentLevel[part][parts[i+1]] && currentLevel[part][parts[i+1]].data) { 
                    currentLevel = currentLevel[part];
                }
                 else {
                    return null; 
                }
            } else {
                return null; 
            }
        }
        return null; 
    }


    async function applyTemplate(fullTemplateKey) {
        if (!fullTemplateKey) { 
            if (Object.keys(collectedFormData).length > 0) {
                 const result = await Swal.fire({
                    title: 'Limpar Plano Atual?',
                    text: "Deseja limpar o plano atual e começar um novo do zero?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, limpar',
                    cancelButtonText: 'Não, manter'
                });
                if (result.isConfirmed) {
                    clearState(true);
                } else {
                    return; 
                }
            } else {
                clearState(true); 
            }
            wizardContainer.style.display = 'block';
            hideCompletionSection();
            return;
        }

        const template = getTemplateDataByKey(fullTemplateKey);

        if (!template || !template.data) {
            console.error("Template não encontrado ou inválido:", fullTemplateKey);
             Swal.fire('Erro', 'Modelo não encontrado ou inválido.', 'error');
            return;
        }

        if (Object.keys(collectedFormData).length > 0) {
            const result = await Swal.fire({
                title: 'Aplicar Modelo?',
                html: `Aplicar o modelo "<strong>${escapeHtml(template.name)}</strong>" substituirá os dados atuais do seu plano. Deseja continuar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, aplicar!',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) {
                return;
            }
        }

        collectedFormData = { ...template.data };
        currentStep = 0; 
        saveState();
        renderStep(currentStep);
        wizardContainer.style.display = 'block';
        hideCompletionSection();
        Swal.fire({
            icon: 'success',
            title: 'Modelo Aplicado!',
            html: `O modelo "<strong>${escapeHtml(template.name)}</strong>" foi aplicado. Revise e ajuste conforme necessário.<br><br><strong>Atenção:</strong> Lembre-se de substituir os placeholders como '[Nome do Software]', '[Persona]', etc., pelos seus próprios dados.`,
            showConfirmButton: true
        });
    }


    // --- AI ASSISTANCE MODAL Functions ---
    function resetModalUI() {
        aiAssistanceOutputEl.innerHTML = 'O resultado da IA será exibido aqui.'; 
        aiApplyOutputBtn.style.display = 'none';
        aiDiscardOutputBtn.style.display = 'none';
        aiCopyOutputBtn.style.display = 'none';
        aiCloseModalBtn.textContent = 'Fechar';
        currentAiModalOutput = "";
        modalAiTargetFieldId = null;
        modalAiIsCkEditorTarget = false;
        modalAiFriendlyActionName = "";
    }

    function buildFullContext() {
        collectStepData();
        let context = {
            theme: collectedFormData['step0_q0'] || "Não definido",
            problem: collectedFormData['step0_q1'] || "Não definido",
            personaDesc: stripHtml(collectedFormData['step1_q0']) || "Não definido",
            audienceLevel: getOptionLabel('step1_q1', collectedFormData['step1_q1']) || "Não definido",
            readerOutcome: collectedFormData['step2_q0'] || "Não definido",
            ebookObjective: getOptionLabel('step2_q1', collectedFormData['step2_q1']) || "Não definido",
            title: collectedFormData['step3_q0'] || "Não definido",
            subtitle: collectedFormData['step3_q1'] || "Não definido",
            mainChapters: collectedFormData['step4_q0'] || "Não definidos",
            detailedToc: stripHtml(collectedFormData['step4_q1']) || "Não definidos",
            tone: getOptionLabel('step6_q0', collectedFormData['step6_q0']) || "Não definido",
            distributionModel: getOptionLabel('step10_q0', collectedFormData['step10_q0']) || "Não definido",
            marketingChannelsList: [],
        };

        const marketingChannelsQuestion = steps.find(step => step.title.startsWith("11."))?.questions.find(q => q.id === 'step10_q1');
        if (marketingChannelsQuestion && marketingChannelsQuestion.options) {
            marketingChannelsQuestion.options.forEach(opt => {
                if (collectedFormData[`step10_q1_${opt.value}`] === 'on') {
                    context.marketingChannelsList.push(opt.label);
                }
            });
            if (collectedFormData['step10_q1_other'] === 'on' && collectedFormData['step10_q1_other_text']) {
                context.marketingChannelsList.push(`Outro: ${collectedFormData['step10_q1_other_text']}`);
            }
        }
        if(context.marketingChannelsList.length === 0) context.marketingChannelsList = "Não definidos";
        else context.marketingChannelsList = context.marketingChannelsList.join(', ');

        return context;
    }


    async function handleAiAssistanceAction(actionType) {
        if (!aiEnabled || !aiModel) {
             Swal.fire({
                icon: 'warning',
                title: 'IA Não Configurada',
                text: 'As funcionalidades de IA não estão habilitadas. Por favor, verifique sua API Key.',
                confirmButtonText: 'Configurar API Key',
                showCancelButton: true,
                cancelButtonText: 'Agora não'
            }).then((result) => {
                if (result.isConfirmed) {
                    promptForAPIKey(true);
                }
            });
            aiAssistanceModalInstance.hide();
            return;
        }

        const context = buildFullContext();
        const suggestionCount = parseInt(aiSuggestionCountModalEl.value) || 3;

        let prompt = "";
        modalAiTargetFieldId = null;
        modalAiIsCkEditorTarget = false;
        modalAiFriendlyActionName = ""; 
        let expectsJson = false;
        let markdownAction = false;

        const clickedButton = aiAssistanceModalEl.querySelector(`button[data-action-type="${actionType}"]`);
        if (clickedButton && clickedButton.querySelector('h6')) {
            modalAiFriendlyActionName = clickedButton.querySelector('h6').textContent.trim();
        }


        aiAssistanceOutputEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Gerando...</span></div><p class="mt-2">A IA está processando sua solicitação...</p></div>';
        aiApplyOutputBtn.style.display = 'none';
        aiDiscardOutputBtn.style.display = 'none';
        aiCopyOutputBtn.style.display = 'none';

        switch (actionType) {
            case 'generateIntroduction':
                prompt = `Você é um assistente de escrita. Baseado no plano abaixo, escreva um rascunho de INTRODUÇÃO de [N_SUGESTOES] parágrafos (aprox. 300-500 palavras).
**Plano:**
- Título: ${context.title}
- Tema: ${context.theme}
- Problema: ${context.problem}
- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})
- Resultado: ${context.readerOutcome}
- Capítulos: ${context.mainChapters || "Não definidos"}
- Tom: ${context.tone}
**Instruções:** Gancho inicial, apresente problema/oportunidade, a solução (eBook), breve panorama dos capítulos, credibilidade (opcional), chamada para leitura. Mantenha o tom planejado.
Formato: Texto corrido.`;
                break;
            case 'reviewPlan':
                markdownAction = true;
                prompt = `Você é um editor experiente. Analise o plano abaixo e forneça feedback construtivo sobre:
1. Alinhamento (título, problema, público, resultado, capítulos)
2. Clareza da Proposta de Valor
3. Completude dos Capítulos (faltam? redundantes?)
4. Engajamento do Título/Subtítulo
5. CTA (implícita/explícita, considerando o objetivo do autor)
6. Sugestões Gerais para Melhoria ([N_SUGESTOES] principais)
**Plano:**
- Tema: ${context.theme}
- Problema: ${context.problem}
- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})
- Objetivo Autor: ${context.ebookObjective}
- Resultado Leitor: ${context.readerOutcome}
- Título: ${context.title}
- Subtítulo: ${context.subtitle}
- Capítulos: ${context.mainChapters || "Não definidos"}
- TOC Detalhada (se houver): ${context.detailedToc.substring(0,300)}${context.detailedToc.length > 300 ? '...' : ''}
- Tom: ${context.tone}
Formato: Tópicos com markdown. Use linguagem clara e acionável.`;
                break;
            case 'generateSummary': 
                modalAiTargetFieldId = 'step4_q0';
                expectsJson = true; 
                prompt = `Baseado no contexto, gere uma lista de [N_CAPITULOS] TÍTULOS DE CAPÍTULOS principais para um eBook.
**Contexto:**
- Tema: ${context.theme}
- Problema que o eBook resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
- Resultado esperado para o leitor: ${context.readerOutcome}
**Instruções:** Os títulos dos capítulos devem ser lógicos, sequenciais e atraentes.
Formato da Resposta (JSON Array de Strings):
["Título do Capítulo 1", "Título do Capítulo 2", ...]`;
                break;
            case 'generateDetailedTOC':
                modalAiTargetFieldId = 'step4_q1';
                modalAiIsCkEditorTarget = true;
                expectsJson = true;
                prompt = `Gere uma ESTRUTURA DE CAPÍTULOS E SUB-TÓPICOS detalhada para um eBook.
${context.mainChapters ? `Use esta lista de capítulos como base:\n${context.mainChapters}\nPara cada um, detalhe [N_SUBTOPICOS] sub-tópicos relevantes.` : `Crie [N_CAPITULOS] capítulos principais e, para cada capítulo, detalhe [N_SUBTOPICOS] sub-tópicos relevantes.`}
**Contexto:**
- Tema: ${context.theme}
- Problema que o eBook resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
- Resultado esperado para o leitor: ${context.readerOutcome}
**Instruções:** Os sub-tópicos devem ser específicos, lógicos e cobrir os aspectos essenciais de cada capítulo.
Formato da Resposta (JSON Array de Objetos):
[
  { "title": "Título do Capítulo 1", "subtopics": ["Subtópico 1.1", "Subtópico 1.2"] },
  { "title": "Título do Capítulo 2", "subtopics": ["Subtópico 2.1", "Subtópico 2.2"] }
  ...
]`;
                break;
            case 'analyzeTitleSubtitle':
                markdownAction = true;
                prompt = `Analise o título e subtítulo abaixo para um eBook, considerando engajamento, clareza e potencial de SEO.
**Contexto do eBook:**
- Tema: ${context.theme}
- Problema que resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
**Título Atual:** ${context.title}
**Subtítulo Atual:** ${context.subtitle}
**Instruções:**
Forneça uma análise em tópicos:
1.  **Pontos Fortes:** O que funciona bem?
2.  **Pontos a Melhorar:** Onde pode ser mais claro, mais atraente ou melhor para SEO?
3.  **Sugestões ([N_SUGESTOES] opções):** Apresente alternativas ou melhorias para o título e/ou subtítulo.
Formato: Markdown.`;
                break;
            case 'generateMarketingDescription':
                prompt = `Crie [N_SUGESTOES] opções de descrição curta (sinopse) para um eBook, ideal para uso em lojas online (Amazon, Hotmart) ou posts de redes sociais.
**Informações do eBook:**
- Título: ${context.title}
- Subtítulo: ${context.subtitle}
- Problema que resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
- Principal benefício/resultado para o leitor: ${context.readerOutcome}
- Tom de voz: ${context.tone}
**Instruções:** Cada descrição deve ser persuasiva, destacar o benefício principal e ter entre 50-150 palavras. Mantenha o tom de voz planejado.
Formato: Separe cada opção de descrição com "---".
Opção 1:
[Texto da descrição 1]
---
Opção 2:
[Texto da descrição 2]
...`;
                break;
            case 'suggestSeoKeywords':
                prompt = `Sugira uma lista de [N_SUGESTOES] palavras-chave relevantes para otimização de SEO de um eBook e seu material de divulgação.
**Informações do eBook:**
- Tema Principal: ${context.theme}
- Problema que resolve: ${context.problem}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
**Instruções:**
Liste palavras-chave primárias (alto volume, mais genéricas) e secundárias (long-tail, mais específicas). Indique a intenção de busca (informacional, transacional, etc.) se possível.
Formato:
**Palavras-Chave Primárias:**
- [Palavra-chave 1] (Intenção: Ex: Informacional)
- [Palavra-chave 2] (Intenção: Ex: Navegacional)
**Palavras-Chave Secundárias (Long-Tail):**
- [Palavra-chave Long-Tail 1] (Intenção: Ex: Transacional)
- [Palavra-chave Long-Tail 2] (Intenção: Ex: Informacional específica)
...`;
                break;
            case 'brainstormSupportContent':
                prompt = `Gere [N_SUGESTOES] ideias para conteúdo de apoio (artigos de blog, posts de redes sociais, vídeos) para promover um eBook.
**Informações do eBook:**
- Tema Principal: ${context.theme}
- Título do eBook: ${context.title}
- Capítulos Principais (se houver): ${context.mainChapters || "Não definidos, baseie-se no tema."}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}
**Instruções:** As ideias devem ser relevantes ao tema do eBook e interessantes para o público-alvo. Podem ser aprofundamentos de capítulos, bastidores, estudos de caso relacionados, etc.
Formato: Liste cada ideia como um título ou tema curto.
1. [Ideia 1]
2. [Ideia 2]
...`;
                break;
            case 'analyzePlannedTone':
                markdownAction = true;
                prompt = `Analise o tom de voz planejado para um eBook e sua adequação ao público e tema.
**Informações do eBook:**
- Tom de Voz Planejado: ${context.tone}
- Descrição do Estilo (se houver): ${collectedFormData['step6_q1'] || "Não fornecida"}
- Tema Principal: ${context.theme}
- Público-alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (Nível: ${context.audienceLevel})
**Instruções:**
Avalie se o tom planejado é:
1.  Consistente com a descrição de estilo (se houver).
2.  Adequado para o público-alvo e seu nível de conhecimento.
3.  Apropriado para o tema do eBook.
Forneça feedback construtivo e sugestões para refinar o tom, se necessário, incluindo [N_SUGESTOES] exemplos de como certas frases poderiam ser adaptadas.
Formato: Markdown, com tópicos.`;
                break;

            default:
                aiAssistanceOutputEl.textContent = "Ação de IA não reconhecida.";
                return;
        }

        await getGeminiSuggestions(prompt, actionType, null, null, null, suggestionCount);

        if (currentAiModalOutput) {
            if (markdownAction && typeof marked !== 'undefined') {
                try {
                    aiAssistanceOutputEl.innerHTML = marked.parse(currentAiModalOutput);
                } catch (e) {
                    console.error("Erro ao renderizar Markdown no modal AI:", e);
                    aiAssistanceOutputEl.textContent = currentAiModalOutput; 
                }
            } else if (expectsJson) {
                 aiAssistanceOutputEl.innerHTML = `<pre>${escapeHtml(currentAiModalOutput)}</pre>`;
            }
            else {
                 aiAssistanceOutputEl.innerHTML = `<p>${currentAiModalOutput.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>')}</p>`;
            }

            if (modalAiTargetFieldId && (actionType === 'generateSummary' || actionType === 'generateDetailedTOC')) {
                 aiApplyOutputBtn.style.display = 'inline-block';
            } else {
                 aiApplyOutputBtn.style.display = 'none';
            }
            aiDiscardOutputBtn.style.display = 'inline-block';
            aiCopyOutputBtn.style.display = 'inline-block';
            aiCloseModalBtn.textContent = 'Cancelar';
        } else if (!aiAssistanceOutputEl.querySelector('.alert-danger')) {
             aiAssistanceOutputEl.innerHTML = '<div class="alert alert-warning p-3">Não foi possível gerar a sugestão ou a resposta da IA foi vazia. Tente novamente.</div>';
        }
    }

    // --- Wizard Functions ---
    async function renderStep(index) {
        tooltipList.forEach(tooltip => tooltip.dispose());
        tooltipList = [];
        hideInlineAiButton(); // Hide button when changing steps

        const editorIdsInPreviousStep = steps[currentStep]?.questions.filter(q => ckEditorFieldIds.includes(q.id)).map(q => q.id) || [];
        for (const editorId of editorIdsInPreviousStep) {
            if (ckEditorInstances[editorId]) {
                try { await ckEditorInstances[editorId].destroy(); }
                catch (err) { console.error("Error destroying editor:", editorId, err); }
                delete ckEditorInstances[editorId];
            }
        }

        stepsContainer.innerHTML = '';
        validationErrorEl.style.display = 'none';

        const step = steps[index];
        const stepDiv = document.createElement('div');
        stepDiv.className = 'wizard-step active';
        stepDiv.setAttribute('data-step-index', index);

        const titleEl = document.createElement('h3');
        titleEl.className = 'step-title'; titleEl.textContent = step.title;
        stepDiv.appendChild(titleEl);

        const questionPromises = step.questions.map(async (qData) => {
            const formGroup = document.createElement('div');
            formGroup.className = 'mb-4 position-relative';
            let labelWrapper = document.createElement('div');
            labelWrapper.className = 'd-flex justify-content-between align-items-center mb-1 flex-wrap';
            const label = document.createElement('label');
            label.htmlFor = qData.id; label.className = 'form-label mb-0';
            label.innerHTML = qData.label + (qData.required ? '<span class="required-field-marker">*</span>' : '');
            if (qData.tooltip) { label.setAttribute('data-bs-toggle', 'tooltip'); label.setAttribute('data-bs-placement', 'top'); label.title = qData.tooltip; }
            labelWrapper.appendChild(label);

            if (aiEnabled && qData.aiSuggestion) {
                 const aiButtonContainer = document.createElement('div');
                 aiButtonContainer.className = 'inline-ai-button-container';

                 const countInput = document.createElement('input');
                 countInput.type = 'number';
                 countInput.className = 'form-control form-control-sm ai-count-input';
                 countInput.id = `${qData.id}_ai_count`;
                 countInput.min = "1"; countInput.max = "10";
                 countInput.value = qData.aiSuggestion.countDefault || "3";
                 if (!qData.aiSuggestion.type.match(/persona|coverConcept|subtopicsFromChapters/) &&
                     !(qData.aiSuggestion.type === 'titles' || qData.aiSuggestion.type === 'chapters')) { 
                    aiButtonContainer.appendChild(countInput);
                 }


                 const aiButton = document.createElement('button');
                 aiButton.type = 'button'; aiButton.className = 'btn btn-sm btn-info btn-ai-action';
                 aiButton.dataset.questionId = qData.id; aiButton.dataset.suggestionType = qData.aiSuggestion.type;
                 const aiBaseId = `${qData.id}_${qData.aiSuggestion.type}`;
                 aiButton.dataset.targetDivId = `${aiBaseId}_suggestions`;
                 aiButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-magic me-1" viewBox="0 0 16 16"><path d="M9.5 2.672a.5.5 0 1 0 1 0V.843a.5.5 0 0 0-1 0v1.829Zm4.5.035A.5.5 0 0 0 13.293 2L12 3.293a.5.5 0 1 0 .707.707L14 2.707ZM7.293 4A.5.5 0 1 0 8 3.293L6.707 2A.5.5 0 0 0 6 2.707L7.293 4Zm-.621 2.5a.5.5 0 0 0 0-1H4.843a.5.5 0 1 0 0 1h1.829Zm8.485 0a.5.5 0 1 0 0-1h-1.829a.5.5 0 0 0 0 1h1.829ZM13.293 12A.5.5 0 0 0 14 11.293L12.707 10a.5.5 0 1 0-.707.707L13.293 12Zm-5.786 1.328a.5.5 0 1 0-1 0v1.829a.5.5 0 0 0 1 0V13.33Zm-4.5.035a.5.5 0 0 0 .707.707L4 12.707a.5.5 0 0 0-.707-.707L2 13.293a.5.5 0 0 0 .707.707ZM11.5 6.5a.5.5 0 0 0-1 0V8.33a.5.5 0 0 0 1 0V6.5Zm-6.932 1.432a.5.5 0 0 0-.52.045L2.509 9.417a.5.5 0 0 0 .487.878l1.537-1.025a.5.5 0 0 0 .045-.52ZM12.94 9.417l1.538 1.025a.5.5 0 0 0 .487-.878l-1.537-1.025a.5.5 0 0 0-.52-.045L10.5 9.831l.03-.055a.5.5 0 0 0-.03.055l2.44 1.626Z"/></svg>
                     <span>${qData.aiSuggestion.buttonText || 'Sugerir'}</span>
                     <span class="spinner-border spinner-border-sm ms-2 align-middle"></span>`;
                 aiButtonContainer.appendChild(aiButton);
                 labelWrapper.appendChild(aiButtonContainer);
            }
            formGroup.appendChild(labelWrapper);

            if (qData.description) {
                const desc = document.createElement('div'); desc.className = 'form-text';
                desc.textContent = qData.description; formGroup.appendChild(desc);
            }

            if (aiEnabled && qData.id === 'step4_q1') { 
                const inlineAiButtonContainer = document.createElement('div');
                inlineAiButtonContainer.className = 'inline-ai-button-container';

                const countInputSubtopics = document.createElement('input');
                countInputSubtopics.type = 'number';
                countInputSubtopics.className = 'form-control form-control-sm ai-count-input';
                countInputSubtopics.id = `${qData.id}_inline_ai_count`;
                countInputSubtopics.min = "1"; countInputSubtopics.max = "5";
                countInputSubtopics.value = "3"; 
                countInputSubtopics.title = "Sub-tópicos por capítulo";
                inlineAiButtonContainer.appendChild(countInputSubtopics);

                const inlineAiButton = document.createElement('button');
                inlineAiButton.type = 'button';
                inlineAiButton.className = 'btn btn-sm btn-outline-info btn-ai-action';
                inlineAiButton.dataset.suggestionType = 'subtopicsFromChapters';
                inlineAiButton.dataset.sourceFieldId = 'step4_q0'; 
                inlineAiButton.dataset.targetEditorId = 'step4_q1'; 
                inlineAiButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-magic me-1" viewBox="0 0 16 16"><path d="M9.5 2.672a.5.5 0 1 0 1 0V.843a.5.5 0 0 0-1 0v1.829Zm4.5.035A.5.5 0 0 0 13.293 2L12 3.293a.5.5 0 1 0 .707.707L14 2.707ZM7.293 4A.5.5 0 1 0 8 3.293L6.707 2A.5.5 0 0 0 6 2.707L7.293 4Zm-.621 2.5a.5.5 0 0 0 0-1H4.843a.5.5 0 1 0 0 1h1.829Zm8.485 0a.5.5 0 1 0 0-1h-1.829a.5.5 0 0 0 0 1h1.829ZM13.293 12A.5.5 0 0 0 14 11.293L12.707 10a.5.5 0 1 0-.707.707L13.293 12Zm-5.786 1.328a.5.5 0 1 0-1 0v1.829a.5.5 0 0 0 1 0V13.33Zm-4.5.035a.5.5 0 0 0 .707.707L4 12.707a.5.5 0 0 0-.707-.707L2 13.293a.5.5 0 0 0 .707.707ZM11.5 6.5a.5.5 0 0 0-1 0V8.33a.5.5 0 0 0 1 0V6.5Zm-6.932 1.432a.5.5 0 0 0-.52.045L2.509 9.417a.5.5 0 0 0 .487.878l1.537-1.025a.5.5 0 0 0 .045-.52ZM12.94 9.417l1.538 1.025a.5.5 0 0 0 .487-.878l-1.537-1.025a.5.5 0 0 0-.52-.045L10.5 9.831l.03-.055a.5.5 0 0 0-.03.055l2.44 1.626Z"/></svg>
                    <span>Gerar Sub-tópicos com IA</span>
                    <span class="spinner-border spinner-border-sm ms-2 align-middle"></span>`;
                inlineAiButtonContainer.appendChild(inlineAiButton);
                formGroup.appendChild(inlineAiButtonContainer);
            }


            let inputElement;
            switch (qData.type) {
                case 'textarea':
                    inputElement = document.createElement('textarea');
                    inputElement.className = 'form-control';
                    inputElement.rows = qData.rows || 3;
                    if (qData.placeholder) inputElement.placeholder = qData.placeholder;
                    break;
                case 'text':
                    inputElement = document.createElement('input'); inputElement.type = 'text';
                    inputElement.className = 'form-control';
                    if (qData.placeholder) inputElement.placeholder = qData.placeholder;
                    break;
                case 'select':
                    inputElement = document.createElement('select'); inputElement.className = 'form-select';
                    if (!qData.options.find(opt => opt.value === "")) {
                         const defOpt = document.createElement('option'); defOpt.value = ""; defOpt.textContent = "-- Selecione --";
                         if (!collectedFormData[qData.id]) defOpt.selected = true; inputElement.appendChild(defOpt);
                    }
                    qData.options.forEach(optData => {
                        const opt = document.createElement('option'); opt.value = optData.value; opt.textContent = optData.label; inputElement.appendChild(opt);
                    });
                    break;
                 case 'radio': case 'checkbox':
                    const choiceContainer = document.createElement('div'); choiceContainer.id = qData.id;
                    qData.options.forEach((optData, optIdx) => {
                        const wrap = document.createElement('div'); wrap.className = 'form-check';
                        const inp = document.createElement('input'); inp.type = qData.type; inp.className = 'form-check-input';
                        inp.name = qData.type === 'radio' ? qData.id : `${qData.id}_${optData.value}`;
                        inp.id = `${qData.id}_${optData.value}_opt`;
                        inp.value = optData.value;
                        const optLabel = document.createElement('label'); optLabel.className = 'form-check-label';
                        optLabel.htmlFor = inp.id; optLabel.textContent = optData.label;
                        wrap.appendChild(inp); wrap.appendChild(optLabel); choiceContainer.appendChild(wrap);
                    });
                    if (qData.otherOption) {
                        const otherWrap = document.createElement('div'); otherWrap.className = 'form-check';
                        const otherInp = document.createElement('input'); otherInp.type = qData.type;
                        otherInp.className = 'form-check-input other-option-trigger';
                        otherInp.name = qData.type === 'radio' ? qData.id : `${qData.id}_other`;
                        otherInp.id = `${qData.id}_other_trigger`; otherInp.value = 'other';
                        const otherLabel = document.createElement('label'); otherLabel.className = 'form-check-label';
                        otherLabel.htmlFor = otherInp.id; otherLabel.textContent = 'Outro:';
                        const otherTextInp = document.createElement('input'); otherTextInp.type = 'text';
                        otherTextInp.className = 'form-control other-text-input';
                        otherTextInp.id = `${qData.id}_other_text`; otherTextInp.name = `${qData.id}_other_text`;
                        otherTextInp.placeholder = 'Por favor, especifique'; otherTextInp.style.display = 'none';
                        otherWrap.appendChild(otherInp); otherWrap.appendChild(otherLabel); otherWrap.appendChild(otherTextInp);
                        choiceContainer.appendChild(otherWrap);
                    }
                    formGroup.appendChild(choiceContainer);
                    break;
                default: inputElement = document.createElement('input'); inputElement.type = 'text'; inputElement.className = 'form-control';
            }

            if (inputElement) {
                inputElement.id = qData.id; inputElement.name = qData.id;
                if (qData.required) { inputElement.required = true; inputElement.setAttribute('aria-required', 'true'); }
                if (collectedFormData[qData.id] && !ckEditorFieldIds.includes(qData.id)) {
                    inputElement.value = collectedFormData[qData.id];
                }
                formGroup.appendChild(inputElement);

                if (qData.type === 'textarea' && ckEditorFieldIds.includes(qData.id)) {
                    inputElement.style.display = 'none';
                    const wrapperDiv = document.createElement('div');
                    wrapperDiv.classList.add('ckeditor-wrapper-class');
                    wrapperDiv.dataset.targetValidationId = qData.id; // Used for validation styling
                    formGroup.insertBefore(wrapperDiv, inputElement.nextSibling);
                    wrapperDiv.appendChild(inputElement);

                    try {
                        const editor = await ClassicEditor.create(inputElement, {
                            toolbar: { items: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo' ], shouldNotGroupWhenFull: true },
                            language: 'pt-br',
                        });
                        ckEditorInstances[qData.id] = editor;
                        if (collectedFormData[qData.id]) {
                            editor.setData(collectedFormData[qData.id]);
                        }
                        editor.model.document.on('change:data', scheduleAutoSave);

                        // --- Inline AI Button Logic for this CKEditor instance ---
                        if(aiEnabled) {
                            const viewDocument = editor.editing.view.document;
                            
                            editor.editing.view.on('focus', () => {
                                if (!editor.model.document.selection.isCollapsed) {
                                    showInlineAiButton(editor);
                                }
                            });
                            editor.editing.view.on('blur', () => {
                                // Small delay to allow click on floating button before hiding
                                setTimeout(() => {
                                    if (!inlineAiFloatingButton.matches(':hover') && !inlineAiFloatingButton.querySelector('.dropdown-menu.show')) {
                                        hideInlineAiButton();
                                    }
                                }, 150);
                            });

                            editor.model.document.selection.on('change:range', () => {
                                clearTimeout(debounceTimerInlineButton);
                                debounceTimerInlineButton = setTimeout(() => {
                                    if (editor.editing.view.state === 'focused') {
                                        if (!editor.model.document.selection.isCollapsed) {
                                            showInlineAiButton(editor);
                                        } else {
                                            hideInlineAiButton();
                                        }
                                    }
                                }, 200); // Debounce to avoid rapid show/hide
                            });
                        }
                        // --- End of Inline AI Button Logic ---

                    } catch (error) {
                        console.error(`Error initializing CKEditor for ${qData.id}:`, error);
                        inputElement.style.display = 'block';
                        wrapperDiv.remove();
                    }
                }
            } else if (qData.type === 'radio' || qData.type === 'checkbox') {
                 const container = formGroup.querySelector(`#${qData.id}`);
                 if (container) {
                     if (qData.type === 'radio') {
                         const valToSel = collectedFormData[qData.id];
                         if (valToSel) {
                            const radioToChk = container.querySelector(`input[value="${valToSel}"]`);
                            if (radioToChk) {
                                radioToChk.checked = true;
                                if (valToSel === 'other') radioToChk.dispatchEvent(new Event('change', {bubbles: true}));
                            }
                         }
                     } else {
                         qData.options.forEach(optData => {
                             const chk = container.querySelector(`input[name="${qData.id}_${optData.value}"]`);
                             if (chk && collectedFormData[chk.name] === 'on') chk.checked = true;
                         });
                     }
                     const otherChk = container.querySelector(`#${qData.id}_other_trigger`);
                     const otherTxtInp = container.querySelector(`#${qData.id}_other_text`);
                     if (otherChk && otherTxtInp) {
                          if ( (qData.type === 'radio' && collectedFormData[qData.id] === 'other') ||
                               (qData.type === 'checkbox' && collectedFormData[otherChk.name] === 'on') ) {
                                otherChk.checked = true; otherTxtInp.style.display = 'block';
                                otherTxtInp.value = collectedFormData[otherTxtInp.name] || '';
                                otherTxtInp.required = qData.required;
                          } else { otherTxtInp.required = false; }
                     }
                 }
            }

            if (aiEnabled && qData.aiSuggestion) {
                const suggestionsDiv = document.createElement('div');
                suggestionsDiv.id = `${qData.id}_${qData.aiSuggestion.type}_suggestions`;
                suggestionsDiv.className = 'ai-suggestions-container'; suggestionsDiv.style.display = 'none';
                formGroup.appendChild(suggestionsDiv);
            }
            const feedbackDiv = document.createElement('div'); feedbackDiv.className = 'invalid-feedback';
            feedbackDiv.textContent = 'Este campo é obrigatório.'; formGroup.appendChild(feedbackDiv);
            stepDiv.appendChild(formGroup);
        });

        await Promise.all(questionPromises);

        stepsContainer.appendChild(stepDiv);
        progressIndicator.textContent = `Etapa ${index + 1} de ${steps.length}`;
        prevBtn.disabled = index === 0;
        nextBtn.textContent = index === steps.length - 1 ? 'Finalizar Planejamento' : 'Próximo';
        nextBtn.className = index === steps.length - 1 ? 'btn btn-success' : 'btn btn-primary';

        const tooltipTriggerList = stepDiv.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

        const firstVisibleInput = stepDiv.querySelector('input:not([type="hidden"]):not([type="radio"]):not([type="checkbox"]), select, textarea:not([style*="display: none"])');
        if (firstVisibleInput && !ckEditorFieldIds.includes(firstVisibleInput.id)) {
             setTimeout(() => firstVisibleInput.focus(), 50);
        } else {
            const firstCkId = step.questions.find(q => ckEditorFieldIds.includes(q.id))?.id;
            if (firstCkId && ckEditorInstances[firstCkId]) {
                setTimeout(() => {
                    try {
                        ckEditorInstances[firstCkId].editing.view.focus();
                    } catch (e) { console.warn("CKEditor focus error", e); }
                }, 150);
            } else if (firstVisibleInput) {
                setTimeout(() => firstVisibleInput.focus(), 50);
            }
        }
        attachOtherOptionListeners(stepDiv);
    }

    function attachOtherOptionListeners(stepElement) {
        const otherTriggers = stepElement.querySelectorAll('.other-option-trigger');
        otherTriggers.forEach(trigger => {
            const textInputId = trigger.id.replace('_trigger', '_text');
            const textInput = stepElement.querySelector(`#${textInputId}`);
            if (!textInput) return;

            const questionIdForRequired = trigger.type === 'radio' ? trigger.name : trigger.name.replace('_other', '');
            const questionDefinition = steps[currentStep]?.questions.find(q => q.id === questionIdForRequired);


            const handleTriggerChange = (event) => {
                if (event.target.checked) {
                    textInput.style.display = 'block';
                    textInput.required = questionDefinition?.required ?? false;
                } else {
                    if (trigger.type === 'checkbox') {
                        textInput.style.display = 'none'; textInput.value = '';
                        textInput.required = false; textInput.classList.remove('is-invalid');
                    }
                }
                scheduleAutoSave();
            };
            trigger.addEventListener('change', handleTriggerChange);

            if (trigger.type === 'radio') {
                const radioGroup = stepElement.querySelectorAll(`input[type="radio"][name="${trigger.name}"]`);
                radioGroup.forEach(radio => {
                    if (radio !== trigger) {
                        radio.addEventListener('change', () => {
                            if (radio.checked) {
                                textInput.style.display = 'none'; textInput.value = '';
                                textInput.required = false; textInput.classList.remove('is-invalid');
                                scheduleAutoSave();
                            }
                        });
                    }
                });
             }
        });
    }

    function collectStepData() {
        const currentStepDefinition = steps[currentStep];
        if (!currentStepDefinition) return;

        const form = document.getElementById('wizardForm');
        const formData = new FormData(form);
        const dataFromThisForm = {};

        formData.forEach((value, key) => {
            dataFromThisForm[key] = value;
        });

        currentStepDefinition.questions.forEach(qData => {
            const questionId = qData.id;

            if (ckEditorInstances[questionId] && document.getElementById(questionId)) {
                collectedFormData[questionId] = ckEditorInstances[questionId].getData();
            } else if (qData.type === 'checkbox') {
                qData.options?.forEach(option => {
                    const checkboxName = `${questionId}_${option.value}`;
                    if (dataFromThisForm.hasOwnProperty(checkboxName)) {
                        collectedFormData[checkboxName] = 'on';
                    } else {
                        delete collectedFormData[checkboxName];
                    }
                });
                if (qData.otherOption) {
                    const otherCheckboxName = `${questionId}_other`;
                    const otherTextName = `${questionId}_other_text`;
                    if (dataFromThisForm.hasOwnProperty(otherCheckboxName)) {
                        collectedFormData[otherCheckboxName] = 'on';
                        collectedFormData[otherTextName] = dataFromThisForm[otherTextName] || '';
                    } else {
                        delete collectedFormData[otherCheckboxName];
                        delete collectedFormData[otherTextName];
                    }
                }
            } else if (qData.type === 'radio') {
                if (dataFromThisForm.hasOwnProperty(questionId)) {
                    collectedFormData[questionId] = dataFromThisForm[questionId];
                    if (qData.otherOption) {
                        const otherTextName = `${questionId}_other_text`;
                        if (dataFromThisForm[questionId] === 'other') {
                            collectedFormData[otherTextName] = dataFromThisForm[otherTextName] || '';
                        } else {
                            delete collectedFormData[otherTextName];
                        }
                    }
                }
            } else {
                if (dataFromThisForm.hasOwnProperty(questionId)) {
                    collectedFormData[questionId] = dataFromThisForm[questionId];
                } else {
                    if (document.getElementById(questionId)) {
                       collectedFormData[questionId] = '';
                    }
                }
            }
        });
    }


    // --- Report Generation Functions ---
    function getAnswerForQuestion(qData, data) {
        let answer = { text: null, list: null, isEmpty: true, isHtml: false };
        const value = data[qData.id];

        if (ckEditorFieldIds.includes(qData.id)) {
            const editorContent = data[qData.id] || "";
            if (editorContent.trim() !== "" && editorContent.trim() !== "<p></p>") {
                answer.text = editorContent;
                answer.isEmpty = false;
                answer.isHtml = true;
            }
        } else {
            switch (qData.type) {
                case 'radio': case 'select':
                    if (value) {
                        answer.isEmpty = false;
                        if (value === 'other') {
                            const otherText = data[`${qData.id}_other_text`];
                            answer.text = otherText ? `Outro: ${otherText}` : 'Outro (não especificado)';
                        } else {
                            const option = qData.options?.find(opt => opt.value === value);
                            answer.text = option ? option.label : value;
                        }
                    }
                    break;
                case 'checkbox':
                    const checkedItems = [];
                    qData.options?.forEach(option => {
                        if (data[`${qData.id}_${option.value}`] === 'on') checkedItems.push(option.label);
                    });
                    if (data[`${qData.id}_other`] === 'on') {
                        const otherText = data[`${qData.id}_other_text`];
                        checkedItems.push(otherText ? `Outro: ${otherText}` : 'Outro (não especificado)');
                    }
                    if (checkedItems.length > 0) { answer.list = checkedItems; answer.isEmpty = false; }
                    break;
                case 'textarea': case 'text': default:
                    const textValue = data[qData.id] || "";
                    if (textValue.trim() !== "") { answer.text = textValue; answer.isEmpty = false; }
                    break;
            }
        }
        return answer;
    }

    function generateReportHTML(data, theme = 'default') {
        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        const safeTitle = escapeHtml(ebookTitle);
        let themeStyles = getThemeStyles(theme);
        let reportHTML = `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>Plano Detalhado: ${safeTitle}</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"><style>${themeStyles} pre { white-space: pre-wrap; word-wrap: break-word; font-family: monospace; background-color: rgba(0,0,0,0.03); padding: 0.5em; border-radius: 4px; } body.dark pre { background-color: rgba(255,255,255,0.1); } body.blueish pre { background-color: rgba(14, 165, 233, 0.1); } .ck-content { padding: 0 !important; margin: 0 !important; border: none !important; } .ck-content h2 { font-size: 1.2em; margin-top: 1em; margin-bottom: 0.5em; } .ck-content ul, .ck-content ol { margin-left: 20px; margin-bottom: 0.5em;} </style></head><body class="${theme}"><div class="container report-render-container"><h1>Plano Detalhado: ${safeTitle}</h1>`;
        steps.forEach((step, stepIndex) => {
            reportHTML += `<h2>${escapeHtml(step.title)}</h2>`;
            step.questions.forEach(qData => {
                reportHTML += `<div class="question-block"><strong class="question-label">${escapeHtml(qData.label)}</strong>`;
                const answer = getAnswerForQuestion(qData, data);
                if (answer.isEmpty) {
                    reportHTML += `<p class="empty-answer">- Não preenchido -</p>`;
                } else if (answer.list) {
                    reportHTML += `<ul class="answer-list">${answer.list.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
                } else if (answer.text) {
                    if (answer.isHtml) {
                        reportHTML += `<div class="answer-html ck-content">${answer.text}</div>`; 
                    } else if (answer.text.includes('\n') && answer.text.length > 100) {
                         reportHTML += `<pre class="answer-text">${escapeHtml(answer.text)}</pre>`;
                    } else {
                         reportHTML += `<p class="answer-text">${escapeHtml(answer.text)}</p>`;
                    }
                }
                reportHTML += `</div>`;
            });
            if (stepIndex < steps.length - 1) reportHTML += `<hr />`;
        });
        reportHTML += `</div></body></html>`;
        return reportHTML;
    }

    function generateReportMarkdown(data) {
        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        let md = `# Plano Detalhado: ${ebookTitle}\n\n`;
        const turndownService = typeof TurndownService !== 'undefined' ? new TurndownService({headingStyle: 'atx', codeBlockStyle: 'fenced'}) : null;

        steps.forEach((step, stepIndex) => {
            md += `## ${step.title}\n\n`;
            step.questions.forEach(qData => {
                md += `**${qData.label}**\n\n`;
                const answer = getAnswerForQuestion(qData, data);
                if (answer.isEmpty) md += `*Não preenchido*\n\n`;
                else if (answer.list) md += answer.list.map(item => `- ${item}\n`).join('') + `\n`;
                else if (answer.text) {
                    let textToUse = answer.text;
                    if (answer.isHtml && turndownService) {
                        try {
                            textToUse = turndownService.turndown(answer.text);
                        } catch (e) {
                            console.warn("Falha ao converter HTML para Markdown, usando texto simples:", e);
                            textToUse = stripHtml(answer.text);
                        }
                    } else if (answer.isHtml) {
                        textToUse = stripHtml(answer.text); 
                    }
                    if (textToUse.includes('\n') && !textToUse.startsWith('```') && !textToUse.includes('\n```\n')) {
                         md += "```text\n" + textToUse + "\n```\n\n"; // Specify text for better rendering
                    } else {
                         md += `${textToUse}\n\n`;
                    }
                }
            });
            if (stepIndex < steps.length - 1) md += `***\n\n`;
        });
        return md;
    }

    function generateReportText(data) {
        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        let txt = `Plano Detalhado: ${ebookTitle}\n========================================\n\n`;
        steps.forEach((step, stepIndex) => {
            txt += `== ${step.title} ==\n\n`;
            step.questions.forEach(qData => {
                txt += `${qData.label}:\n`;
                const answer = getAnswerForQuestion(qData, data);
                if (answer.isEmpty) txt += `- Não preenchido -\n\n`;
                else if (answer.list) txt += answer.list.map(item => `  * ${item}\n`).join('') + `\n`;
                else if (answer.text) {
                    const textToUse = answer.isHtml ? stripHtml(answer.text) : answer.text;
                    txt += textToUse.split('\n').map(line => `  ${line}`).join('\n') + `\n\n`;
                }
            });
            if (stepIndex < steps.length - 1) txt += "----------------------------------------\n\n";
        });
        return txt;
    }

     function generateReportJSON(data) {
         const reportData = { title: data['step3_q0'] || "Meu Novo eBook", generatedAt: new Date().toISOString(), plan: {} };
         steps.forEach((step, stepIndex) => {
             const stepKey = `step_${stepIndex + 1}_${step.title.toLowerCase().replace(/[^a-z0-9]+/g, '_')}`;
             reportData.plan[stepKey] = { title: step.title, questions: {} };
             step.questions.forEach(qData => {
                 const answer = getAnswerForQuestion(qData, data);
                 let value = null;
                 if (!answer.isEmpty) {
                    if (answer.list) value = answer.list;
                    else if (answer.isHtml) value = answer.text; 
                    else value = answer.text;
                 }
                 reportData.plan[stepKey].questions[qData.id] = { label: qData.label, answer: value, isHtml: answer.isHtml && !answer.list };
             });
         });
         return JSON.stringify(reportData, null, 2);
     }

    function downloadFile(content, filename, contentType) {
        const blob = new Blob([content], { type: `${contentType};charset=utf-8` });
        saveAs(blob, filename);
    }

    function getFilename(baseName, extension) {
        const titlePart = (collectedFormData['step3_q0'] || 'plano-ebook').normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9\s-]/gi, '').trim().replace(/\s+/g, '-').toLowerCase();
        return `${titlePart || 'plano'}.${extension}`;
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function getThemeStyles(theme) {
        let styles = `body { font-family: 'Inter', sans-serif; line-height: 1.6; margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; } .container { max-width: 800px; margin: 2rem auto; padding: 2rem 3rem; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); transition: background-color 0.3s, border-color 0.3s; } h1 { text-align: center; margin-bottom: 1.5rem; font-weight: 600; } h2 { border-bottom: 2px solid; padding-bottom: 0.5rem; margin-top: 2.5rem; margin-bottom: 1.5rem; font-size: 1.6rem; font-weight: 600; transition: color 0.3s, border-color 0.3s; } .question-block { margin-bottom: 2rem; } .question-label { display: block; margin-bottom: 0.5rem; font-size: 1.1rem; font-weight: 600; transition: color 0.3s; } .answer-text, .answer-html, .answer-list, pre.answer-text { margin-bottom: 1rem; padding-left: 1em; transition: color 0.3s; } .answer-text, pre.answer-text { white-space: pre-wrap; } .answer-html p:first-child { margin-top: 0; } .answer-html p:last-child { margin-bottom: 0;} .answer-list { list-style: disc; padding-left: 2.5em; margin-top: 0.5rem; } .answer-list li { margin-bottom: 0.3rem; } hr { border: 0; height: 1px; margin: 3rem 0; transition: background-color 0.3s; } .empty-answer { font-style: italic; padding-left: 1em; transition: color 0.3s; }`;
        styles += `@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .container { box-shadow: none; border: none; margin: 0; padding:0; max-width: 100%;} } `;
        styles += `.ai-content-updated-flash { animation: flash-background 1s ease-out; } @keyframes flash-background { 0% { background-color: yellow; } 100% { background-color: transparent; } }`;


        switch (theme) {
            case 'dark': styles += `body.dark { background-color: #212529; color: #f8f9fa; } .dark .container { background-color: #343a40; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); border: 1px solid #495057; } .dark h1 { color: #6ea8fe; } .dark h2 { color: #adb5bd; border-color: #495057; } .dark .question-label { color: #ced4da; } .dark .answer-text, .dark .answer-html, .dark .answer-list, .dark pre.answer-text { color: #e9ecef; } .dark hr { background-color: #495057; } .dark .empty-answer { color: #6c757d; }`; break;
            case 'blueish': styles += `body.blueish { background-color: #e0f2fe; color: #075985; } .blueish .container { background-color: #f0f9ff; box-shadow: 0 4px 12px rgba(0, 100, 150, 0.1); border: 1px solid #bae6fd; } .blueish h1 { color: #0284c7; } .blueish h2 { color: #0369a1; border-color: #7dd3fc; } .blueish .question-label { color: #0ea5e9; } .blueish .answer-text, .blueish .answer-html, .blueish .answer-list, .blueish pre.answer-text { color: #075985; } .blueish hr { background-color: #bae6fd; } .blueish .empty-answer { color: #60a5fa; }`; break;
            default: styles += `body.default { background-color: #f8f9fa; color: #343a40; } .default .container { background-color: #ffffff; border: 1px solid #dee2e6; } .default h1 { color: #0d6efd; } .default h2 { color: #495057; border-color: #dee2e6; } .default .question-label { color: #495057; } .default .answer-text, .default .answer-html, .default .answer-list, .default pre.answer-text { color: #212529; } .default hr { background-color: #e9ecef; } .default .empty-answer { color: #6c757d; }`; break;
        }
        return styles;
    }

    function generateSimplePDF(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4' });
        try { 
            doc.addFont('Inter-Regular.ttf', 'Inter', 'normal'); 
            doc.setFont('Inter', 'normal');
        } catch (e) {
            console.warn("Fonte Inter não pôde ser carregada no jsPDF, usando fonte padrão.", e);
        }


        const ebookTitle = data['step3_q0'] || "Meu Novo eBook";
        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 15;
        const maxLineWidth = pageWidth - margin * 2;
        let currentY = 20;

        doc.setFontSize(18);
        doc.text(ebookTitle, pageWidth / 2, currentY, { align: 'center' });
        currentY += 15;

        steps.forEach((step, stepIndex) => {
            if (currentY > doc.internal.pageSize.getHeight() - 30) {
                doc.addPage(); currentY = 20;
            }
            doc.setFontSize(14); doc.setFont(undefined, 'bold');
            let stepTitleLines = doc.splitTextToSize(step.title, maxLineWidth);
            doc.text(stepTitleLines, margin, currentY);
            currentY += (stepTitleLines.length * 6) + 4;
            doc.setFont(undefined, 'normal');

            step.questions.forEach(qData => {
                if (currentY > doc.internal.pageSize.getHeight() - 25) { doc.addPage(); currentY = 20; }
                doc.setFontSize(11); doc.setFont(undefined, 'bold');
                let qLabelLines = doc.splitTextToSize(qData.label, maxLineWidth);
                doc.text(qLabelLines, margin, currentY);
                currentY += (qLabelLines.length * 5) + 2;
                doc.setFont(undefined, 'normal'); doc.setFontSize(10);

                const answer = getAnswerForQuestion(qData, data);
                let answerText = "";
                if (answer.isEmpty) answerText = "- Não preenchido -";
                else if (answer.list) answerText = answer.list.map(item => `  • ${item}`).join('\n');
                else if (answer.text) answerText = answer.isHtml ? stripHtml(answer.text) : answer.text;

                let answerLines = doc.splitTextToSize(answerText, maxLineWidth - 5);
                doc.text(answerLines, margin + 5, currentY);
                currentY += (answerLines.length * 4.5) + 4;
            });
            if (stepIndex < steps.length - 1) {
                if (currentY > doc.internal.pageSize.getHeight() - 15) { doc.addPage(); currentY = 15; }
                doc.line(margin, currentY, pageWidth - margin, currentY);
                currentY += 7;
            }
        });
        return doc;
    }


     function hideCompletionSection() {
        completionSection.style.display = 'none';
        wizardContainer.style.display = 'block';
    }

    // --- Event Listeners ---
    prevBtn.addEventListener('click', () => {
      if (currentStep > 0) {
        collectStepData(); saveState(); currentStep--; renderStep(currentStep);
      }
    });
    nextBtn.addEventListener('click', () => {
        collectStepData();
        if (!validateStep(currentStep)) return;
        saveState();
        if (currentStep < steps.length - 1) {
            currentStep++; renderStep(currentStep);
        } else {
            wizardContainer.style.display = 'none'; completionSection.style.display = 'block';
            completionSection.scrollIntoView({ behavior: 'smooth' });
        }
    });
    saveProgressBtn.addEventListener('click', () => { collectStepData(); saveState(true); });
    resetPlanBtn.addEventListener('click', () => clearState(false));

    if (templateDropdownMenu) {
        templateDropdownMenu.addEventListener('click', (event) => {
            const target = event.target.closest('a.dropdown-item');
            if (target && target.dataset.templateKey !== undefined) {
                event.preventDefault();
                applyTemplate(target.dataset.templateKey);
            }
        });
    }

    downloadBtn.addEventListener('click', async () => { 
        collectStepData();
        const selectedTheme = reportThemeSelector.value;
        const selectedFormat = reportFormatSelector.value;
        let reportContent, contentType, fileExtension;

        switch (selectedFormat) {
             case 'markdown':
                reportContent = generateReportMarkdown(collectedFormData);
                contentType = 'text/markdown'; fileExtension = 'md';
                downloadFile(reportContent, getFilename('plano-ebook', fileExtension), contentType);
                break;
             case 'text':
                reportContent = generateReportText(collectedFormData);
                contentType = 'text/plain'; fileExtension = 'txt';
                downloadFile(reportContent, getFilename('plano-ebook', fileExtension), contentType);
                break;
             case 'json':
                reportContent = generateReportJSON(collectedFormData);
                contentType = 'application/json'; fileExtension = 'json';
                downloadFile(reportContent, getFilename('plano-ebook', fileExtension), contentType);
                break;
             case 'pdf': 
                contentType = 'application/pdf'; fileExtension = 'pdf';
                const { jsPDF } = window.jspdf; 
                const reportHtmlString = generateReportHTML(collectedFormData, selectedTheme);

                const tempContainer = document.createElement('div');
                tempContainer.style.position = 'absolute';
                tempContainer.style.left = '-9999px';
                tempContainer.style.width = '850px'; 
                tempContainer.innerHTML = reportHtmlString;
                document.body.appendChild(tempContainer);

                await new Promise(resolve => setTimeout(resolve, 100)); 
                tempContainer.offsetHeight;

                showLoading(true);

                try {
                    const canvas = await html2canvas(tempContainer.querySelector('.container.report-render-container'), {
                        scale: 2,
                        useCORS: true,
                        logging: false, 
                        width: tempContainer.querySelector('.container.report-render-container').scrollWidth,
                        height: tempContainer.querySelector('.container.report-render-container').scrollHeight,
                        windowWidth: tempContainer.querySelector('.container.report-render-container').scrollWidth,
                        windowHeight: tempContainer.querySelector('.container.report-render-container').scrollHeight
                    });

                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({
                        orientation: 'p',
                        unit: 'mm',
                        format: 'a4'
                    });
                    const imgProps = pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                    let heightLeft = pdfHeight;
                    let position = 0;
                    const pageHeight = pdf.internal.pageSize.getHeight();

                    pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, pdfHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft > 0) {
                        position -= pageHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, pdfHeight);
                        heightLeft -= pageHeight;
                    }

                    pdf.save(getFilename('plano-ebook', fileExtension));
                    Swal.fire('PDF Gerado!', 'Seu plano de eBook foi exportado como PDF visual.', 'success');

                } catch (err) {
                    console.error("Erro ao gerar PDF com html2canvas:", err);
                    Swal.fire({
                        title: 'Erro no PDF Avançado',
                        html: `Ocorreu um erro ao gerar o PDF visual: ${err.message || 'Erro desconhecido'}.<br><br>Um PDF de texto simples será baixado como alternativa.`,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    const fallbackDoc = generateSimplePDF(collectedFormData);
                    fallbackDoc.save(getFilename('plano-ebook-texto-simples', fileExtension));
                } finally {
                    document.body.removeChild(tempContainer);
                    showLoading(false);
                }
                break;
             case 'pdf_simple':
                const simplePdfDoc = generateSimplePDF(collectedFormData);
                simplePdfDoc.save(getFilename('plano-ebook-texto-simples', 'pdf'));
                Swal.fire('PDF Simples Gerado', 'Um PDF com o conteúdo textual do seu plano foi gerado.', 'info');
                break;
             default: // HTML
                reportContent = generateReportHTML(collectedFormData, selectedTheme);
                contentType = 'text/html'; fileExtension = 'html';
                downloadFile(reportContent, getFilename('plano-ebook', fileExtension), contentType);
                break;
        }
    });

    stepsContainer.addEventListener('click', async (event) => {
        const targetButton = event.target.closest('button.btn-ai-action');
        if (!targetButton || !aiEnabled) return;

        const suggestionType = targetButton.dataset.suggestionType;
        const targetDivId = targetButton.dataset.targetDivId;
        const questionId = targetButton.dataset.questionId;
        const sourceFieldId = targetButton.dataset.sourceFieldId;
        const targetEditorId = targetButton.dataset.targetEditorId; 

        const countInputEl = targetButton.parentElement.querySelector('.ai-count-input') || document.getElementById(`${questionId}_inline_ai_count`) || document.getElementById(`${targetEditorId}_inline_ai_count`);
        const suggestionCount = countInputEl ? parseInt(countInputEl.value) : (steps.flatMap(s => s.questions).find(q => q.id === questionId)?.aiSuggestion?.countDefault || 3);

        const context = buildFullContext();
        let prompt = "";
        let expectsJson = false;

        switch (suggestionType) {
            case 'titles':
                expectsJson = true;
                prompt = `Contexto do eBook:\n- Tema: ${context.theme}\n- Problema Resolvido: ${context.problem}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Resultado Esperado: ${context.readerOutcome}\n\nTarefa: Sugira [N_TITULOS] combinações criativas de Título e Subtítulo.\nFormato da Resposta (JSON Array de Objetos EXATO):\n[\n  {"title": "Título 1", "subtitle": "Subtítulo 1"},\n  {"title": "Título 2", "subtitle": "Subtítulo 2", "notes": "Opcional: breve nota sobre a sugestão"}\n]`;
                break;
            case 'chapters':
                expectsJson = true;
                prompt = `Contexto do eBook:\n- Tema: ${context.theme}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Resultado: ${context.readerOutcome}\n${context.mainChapters ? `- Capítulos já pensados (ignorar se for para gerar novos):\n${context.mainChapters}\n` : ''}\nTarefa: Crie um esboço com [N_CAPITULOS] capítulos e, para cada um, [N_SUBTOPICOS] subtópicos.\nFormato da Resposta (JSON Array de Objetos EXATO):\n[\n  { "name": "Nome do Cap 1", "subtopics": ["Subtópico 1.1", "Subtópico 1.2"] },\n  { "name": "Nome do Cap 2", "subtopics": ["Subtópico 2.1"] }\n]`;
                break;
            case 'elevatorPitch': prompt = `Contexto:\n- Tema: ${context.theme}\n- Problema: ${context.problem}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}\n- Resultado: ${context.readerOutcome}\n${context.title ? `- Título: ${context.title}` : ''}\n\nTarefa: Sugira [N_SUGESTOES] "elevator pitches" (resumo em uma frase impactante). Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'persona': prompt = `Contexto:\n- Tema: ${context.theme}\n- Persona Inicial: ${context.personaDesc.substring(0,200) || "Nenhuma"}${context.personaDesc.length > 200 ? '...' : ''}\n\nTarefa: Elabore a persona com mais detalhes: Dores, Objetivos, Canais de Info, Objeções, Demografia/Comportamento. Texto corrido, parágrafos.`; break;
            case 'problem': prompt = `Contexto:\n- Tema: ${context.theme}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n\nTarefa: Sugira [N_SUGESTOES] formas de articular o *problema específico* que este eBook resolve. Foque em tangibilidade. Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'painPoint': prompt = `Contexto:\n- Tema: ${context.theme}\n- Persona: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}\n- Problema Central: ${context.problem}\n\nTarefa: Liste [N_SUGESTOES] *dores ou necessidades específicas* da persona. Seja específico e emocionalmente relevante. Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'outcome': prompt = `Contexto:\n- Tema: ${context.theme}\n- Problema Resolvido: ${context.problem}\n- Nível Público: ${context.audienceLevel}\n\nTarefa: Sugira [N_SUGESTOES] *resultados práticos ou transformações* que o leitor alcançará. Verbos de ação, resultados mensuráveis. Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'extraElements': prompt = `Contexto:\n- Tema: ${context.theme}\n- Nível Público: ${context.audienceLevel}\n- Objetivo: ${context.ebookObjective}\n\nTarefa: Sugira [N_SUGESTOES] *elementos adicionais* (além de caps, intro, conclu) que agregariam valor. Ex: glossários, checklists. Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'writingStyle': prompt = `Contexto:\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Tom: ${context.tone || "Não definido"}\n\nTarefa: Sugira [N_SUGESTOES] adjetivos ou frases curtas descrevendo o *estilo de redação* ideal. Pense em clareza, ritmo, linguagem. Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'coverConcept': prompt = `Contexto:\n- Título: ${context.title || context.theme}\n- Subtítulo: ${context.subtitle || "Não definido"}\n- Tema: ${context.theme}\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''}\n\nTarefa: Sugira [N_SUGESTOES] *conceitos visuais distintos* para a capa. Descreva elementos, cores, fontes, sentimento. Formato livre, separe conceitos com "---".`; break;
            case 'marketingChannels': prompt = `Contexto:\n- Público: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Distribuição: ${context.distributionModel}\n\nTarefa: Liste [N_SUGESTOES] *canais de marketing e divulgação* adequados. Priorize onde o público está. Liste cada um em nova linha. Sem marcadores ou números.`; break;
            case 'launchAction': prompt = `Contexto:\n- Título: ${context.title || context.theme}\n- Distribuição: ${context.distributionModel}\n- Canais Planejados: ${context.marketingChannelsList || 'Não definidos'}\n\nTarefa: Sugira [N_SUGESTOES] ideias criativas e concretas para a *principal ação de lançamento*. Pense em eventos, ofertas. Liste cada ideia em nova linha. Sem marcadores ou números.`; break;
            case 'subtopicsFromChapters':
                const chapterListText = document.getElementById(sourceFieldId)?.value?.trim();
                if (!chapterListText) {
                    Swal.fire('Atenção', 'Por favor, preencha a lista de capítulos principais (campo anterior) antes de gerar os sub-tópicos.', 'info');
                    return;
                }
                prompt = `Contexto do eBook:\n- Tema Principal: ${context.theme}\n- Público-Alvo: ${context.personaDesc.substring(0,200)}${context.personaDesc.length > 200 ? '...' : ''} (${context.audienceLevel})\n- Resultado Esperado: ${context.readerOutcome}\n\nCapítulos Principais Fornecidos:\n${chapterListText}\n\nTarefa: Para CADA UM dos capítulos principais fornecidos acima, detalhe [N_SUBTOPICOS] sub-tópicos relevantes e específicos. Mantenha a progressão lógica.\n\nFormato da Resposta (Use este formato EXATO para cada capítulo fornecido, sem JSON wrapper, apenas o texto):\n[Título do Capítulo Fornecido 1]\n  - Subtópico 1.1\n  - Subtópico 1.2\n\n[Título do Capítulo Fornecido 2]\n  - Subtópico 2.1\n  - Subtópico 2.2\n... (e assim por diante para todos os capítulos fornecidos)`;
                await getGeminiSuggestions(prompt, suggestionType, null, targetButton, targetEditorId, suggestionCount); 
                return;
            default: console.error("Tipo de sugestão AI não reconhecido:", suggestionType); return;
        }
        if (prompt) await getGeminiSuggestions(prompt, suggestionType, targetDivId, targetButton, null, suggestionCount);
    });

    function getOptionLabel(questionId, value) {
        const question = steps.flatMap(s => s.questions).find(q => q.id === questionId);
        if (!question || !question.options || !value) return value;
        if (value === 'other') {
            return `Outro: ${collectedFormData[`${questionId}_other_text`] || 'não especificado'}`;
        }
        const option = question.options.find(opt => opt.value === value);
        return option ? option.label : value;
    }

    // --- Event Listeners for AI Assistance Modal Buttons ---
    aiAssistanceModalEl.addEventListener('click', (event) => {
        const actionButton = event.target.closest('.list-group-item-action[data-action-type]');
        if (actionButton) {
            const actionType = actionButton.dataset.actionType;
            handleAiAssistanceAction(actionType);
        }
    });

    aiApplyOutputBtn.addEventListener('click', () => {
        if (currentAiModalOutput && modalAiTargetFieldId) {
            let processedOutput = currentAiModalOutput;
            if (modalAiFriendlyActionName === "Criar Lista de Capítulos") { 
                try {
                    const chaptersArray = JSON.parse(currentAiModalOutput.replace(/^```json\s*|\s*```$/g, '').trim());
                    if (Array.isArray(chaptersArray)) {
                        processedOutput = chaptersArray.join('\n');
                    } else { throw new Error("JSON não é array"); }
                } catch (e) {
                    console.warn("Falha ao aplicar JSON de lista de capítulos, usando texto bruto:", e);
                }
            } else if (modalAiFriendlyActionName === "Detalhar Sumário (TOC)") { 
                try {
                    const tocArray = JSON.parse(currentAiModalOutput.replace(/^```json\s*|\s*```$/g, '').trim());
                    if (Array.isArray(tocArray)) {
                        processedOutput = tocArray.map(chap => {
                            let editorContent = `<h2>${escapeHtml(chap.title || chap.name)}</h2>`;
                            if (chap.subtopics && chap.subtopics.length > 0) {
                                editorContent += `<ul>${chap.subtopics.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`;
                            }
                            return editorContent;
                        }).join('<p>&nbsp;</p>');
                    } else { throw new Error("JSON não é array"); }
                } catch (e) {
                    console.warn("Falha ao aplicar JSON de TOC, usando texto bruto para HTML:", e);
                    processedOutput = currentAiModalOutput.split('\n').map(line => {
                        const trimmedLine = line.trim();
                        if (!trimmedLine) return "";
                        if (trimmedLine.match(/^(Capítulo|Seção|Parte)\s*\d*[:\.]?/i)) return `<h2>${escapeHtml(trimmedLine)}</h2>`;
                        if (trimmedLine.match(/^\s*[-*\u2022•]\s+/)) return `<ul><li>${escapeHtml(trimmedLine.replace(/^\s*[-*\u2022•]\s+/, ''))}</li></ul>`;
                        return `<p>${escapeHtml(trimmedLine)}</p>`;
                    }).join('').replace(/<\/ul><h2>/g, '</ul><p>&nbsp;</p><h2>').replace(/<\/ul><ul>/g, '').replace(/<\/li><\/ul><ul><li>/g, '</li><li>');
                }
            }


            if (modalAiIsCkEditorTarget && ckEditorInstances[modalAiTargetFieldId]) {
                ckEditorInstances[modalAiTargetFieldId].setData(processedOutput);
            } else if (document.getElementById(modalAiTargetFieldId)) {
                document.getElementById(modalAiTargetFieldId).value = processedOutput;
            } else {
                 console.warn(`Campo alvo ${modalAiTargetFieldId} não encontrado no DOM para aplicação direta.`);
            }
            collectedFormData[modalAiTargetFieldId] = processedOutput; 
            saveState();

            const targetStepDefinition = steps.find(step => step.questions.some(q => q.id === modalAiTargetFieldId));
            if (targetStepDefinition) {
                const targetStepIndex = steps.indexOf(targetStepDefinition);
                 if (targetStepIndex === currentStep) {
                     renderStep(currentStep); 
                 }
            }
            Swal.fire('Aplicado!', `"${modalAiFriendlyActionName}" aplicado ao seu plano.`, 'success');
        }
        resetModalUI();
        aiAssistanceModalInstance.hide();
    });

    aiDiscardOutputBtn.addEventListener('click', () => {
        resetModalUI();
    });

    aiCopyOutputBtn.addEventListener('click', () => {
            if (currentAiModalOutput) {
                navigator.clipboard.writeText(currentAiModalOutput)
                    .then(() => {
                        const originalHTML = aiCopyOutputBtn.innerHTML;
                        aiCopyOutputBtn.innerHTML = `<i class="bi bi-check-lg me-2"></i>Copiado!`;
                        setTimeout(() => { aiCopyOutputBtn.innerHTML = originalHTML; }, 2000);
                    })
                    .catch(err => {
                        console.error('Erro ao copiar do modal AI:', err);
                        Swal.fire('Erro', 'Não foi possível copiar o texto.', 'error');
                    });
            }
        });

    aiAssistanceModalEl.addEventListener('hidden.bs.modal', resetModalUI);

    // Hide inline AI button when clicking outside of an editor or the button itself
    document.addEventListener('click', (event) => {
        if (inlineAiFloatingButton && !inlineAiFloatingButton.contains(event.target)) {
            let clickedInsideEditor = false;
            for (const editorId in ckEditorInstances) {
                const editorElement = ckEditorInstances[editorId].ui.view.element;
                if (editorElement && editorElement.contains(event.target)) {
                    clickedInsideEditor = true;
                    break;
                }
            }
            if (!clickedInsideEditor) {
                hideInlineAiButton();
            }
        }
    });


    // --- Initialization ---
    document.addEventListener('DOMContentLoaded', async () => {
        document.getElementById('currentYear').textContent = new Date().getFullYear();
        
        populateRewriteToneSubmenu(); // Populate based on initial steps data
        createInlineAiFloatingButton(); // Create the button once

        populateTemplateDropdown();

        geminiAPIKey = localStorage.getItem('geminiAPIKey') || '';
        initializeAI();
        updateAPIKeyStatusUI();

        const stateLoaded = loadState();
        if (stateLoaded && Object.keys(collectedFormData).length > 0) {
            wizardContainer.style.display = 'block';
            hideCompletionSection();
        } else {
            currentStep = 0;
            collectedFormData = {};
            wizardContainer.style.display = 'block';
            hideCompletionSection();
        }

        await renderStep(currentStep);

        if (currentStep >= steps.length && steps.length > 0) { 
             currentStep = steps.length -1;
             wizardContainer.style.display = 'none';
             completionSection.style.display = 'block';
         } else if (steps.length === 0) { 
            console.error("Array de passos (steps) está vazio!");
            wizardContainer.innerHTML = "<p class='text-center text-danger'>Erro de configuração: Nenhum passo definido.</p>";
         }
         window.addEventListener('beforeunload', () => { clearTimeout(autoSaveTimer); collectStepData(); saveState(); });
    });
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║          CATÁLOGO DE IMÓVEIS — Cecilia Lima                     ║
 * ║  Fonte única de dados usada por index.html e imovel.html        ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  COMO CADASTRAR UM NOVO IMÓVEL:                                 ║
 * ║                                                                  ║
 * ║  1. Copie o bloco de exemplo abaixo                             ║
 * ║  2. Cole dentro de IMOVEIS.prontos[] ou IMOVEIS.lancamentos[]  ║
 * ║  3. Preencha os campos (veja legenda abaixo)                    ║
 * ║  4. Adicione as fotos na pasta assets/img/                      ║
 * ║  5. Salve — o site atualiza automaticamente                     ║
 * ║                                                                  ║
 * ║  LEGENDA DOS CAMPOS:                                             ║
 * ║  id          → slug único, só letras minúsculas e hífens        ║
 * ║                Ex: "apto-3q-recreio"                            ║
 * ║  titulo      → nome do imóvel exibido no card                   ║
 * ║  preco       → "R$ 950.000" ou "Consulte" ou "A partir de..."  ║
 * ║  condicao    → "À vista ou financiado" / "Caixa" / "MCMV"      ║
 * ║  area        → "92m²" ou "62 a 87m²"                           ║
 * ║  quartos     → "3" ou "2 a 3" ou "0" (para lotes)              ║
 * ║  vagas       → "2" ou "1 a 2" ou "0"                           ║
 * ║  bairro      → nome do bairro                                   ║
 * ║  badge       → "Destaque" / "Oportunidade" / null (sem badge)   ║
 * ║  diferenciais→ array de strings com os pontos-chave             ║
 * ║  descricao   → texto completo do imóvel (aceita \n)             ║
 * ║  fotos       → array com caminhos das imagens                   ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

/* ─── EXEMPLO PARA COPIAR E COLAR ──────────────────────────────────────
{
  id: "SLUG-UNICO",              // ex: "apto-2q-recreio"
  titulo: "Nome do Imóvel",
  preco: "R$ 000.000",
  condicao: "Financiamento disponível",
  area: "00m²",
  quartos: "0",
  vagas: "0",
  bairro: "Nome do Bairro",
  badge: null,                   // ou "Destaque" / "Oportunidade"
  diferenciais: [
    "Diferencial 1",
    "Diferencial 2",
  ],
  descricao: `Descrição completa do imóvel aqui.`,
  fotos: [
    "assets/img/PASTA/foto1.jpg",
    "assets/img/PASTA/foto2.jpg",
  ],
},
──────────────────────────────────────────────────────────────────────── */

const IMOVEIS = {

  /* ══════════════════════════════════════════
     IMÓVEIS PRONTOS
  ══════════════════════════════════════════ */
  prontos: [
    {
  id: "trevi",
  titulo: "Cobertura com 3 Quartos - Barra Olímpica",
  preco: "1.300.000,00",
  condicao: "A vista ou finaciamento",
  area: "173m²",
  quartos: "3 Quartos",
  vagas: "2 Vagas",
  bairro: "Barra Olímpica",
  badge: "Oportunidade",
  badgeClass: "etiqueta-ouro",
  diferenciais: ["Piscina", "Academia", "Portaria 24h", "Salão de festas", "Churrasqueira", "Mercadinho", "Playground", "Espaço kids", "Quadra esportiva", "Varanda gourmet", "Elevador", "Jardim privativo"],
  descricao: `O equilíbrio perfeito entre a segurança de um condomínio e a liberdade de uma casa. Uma oportunidade única de upgrade para sua família em uma das regiões mais desejadas da cidade.

Destaques do Imóvel:

Espaço: 3 quartos (1 suíte) e sala em dois ambientes com excelente iluminação natural.

Lazer Privativo: Amplo terraço com piscina e churrasqueira gourmet.

Vagas: 2 vagas de garagem.

Infraestrutura Completa do Condomínio (Rio 2):

Segurança 24h com monitoramento e patrulha.

Lazer & Esporte: Parque linear com quadras de tênis, poliesportivas, campos de futebol e ciclovia.

Comodidade: Transporte exclusivo do condomínio (ônibus para principais pontos e metrô).

Localização: Próximo ao Shopping Rio 2, colégios de ponta e centros empresariais.

Aviso: Coberturas com essa configuração e localização são raras e vendem rápido.

Agende sua visita hoje mesmo e venha conhecer seu novo lar!`,
  fotos: [
        "assets/img/Trevi/img-7658-1.jpg",
        "assets/img/Trevi/img-7659-2.jpg",
        "assets/img/Trevi/img-7660-3.jpg",
        "assets/img/Trevi/img-7661-4.jpg",
        "assets/img/Trevi/img-7662-5.jpg",
        "assets/img/Trevi/img-7663-6.jpg",
        "assets/img/Trevi/img-7664-7.jpg",
        "assets/img/Trevi/img-7665-8.jpg",
        "assets/img/Trevi/img-7666-9.jpg",
        "assets/img/Trevi/img-7667-10.jpg",
        "assets/img/Trevi/img-7668-11.jpg",
        "assets/img/Trevi/img-7669-12.jpg",
        "assets/img/Trevi/img-7670-13.jpg",
        "assets/img/Trevi/img-7671-14.jpg",
        "assets/img/Trevi/img-7672-15.jpg",
        "assets/img/Trevi/img-7673-16.jpg",
        "assets/img/Trevi/img-7674-17.jpg",
        "assets/img/Trevi/img-7675-18.jpg",
        "assets/img/Trevi/img-7676-19.jpg",
        "assets/img/Trevi/img-7677-20.jpg",
  ],
},


    {
      id: "apto-3q-barra",
      titulo: "Condomínio Estrela do Mar",
      preco: "R$ 900.000",
      condicao: "À vista ou financiado",
      area: "83m²",
      quartos: "2",
      vagas: "1",
      bairro: "Barra da Tijuca",
      badge: "Destaque",
      diferenciais: [
        "Quadra esportiva",
        "Ônibus executivo para Centro, Zona Sul e diversos pontos da Barra",
        "Transporte circular para a estação de metrô",
        "Academia",
        "Sauna",
        "Piscina",
        "Salão de festas",
        "Portaria 24h",
        "Documentação em dia",
      ],
      descricao: `🏖️ EXCELENTE OPORTUNIDADE NA BARRA DA TIJUCA – CONDOMÍNIO ESTRELA DO MAR | ABM

Apartamento à venda com 83 m² em uma das regiões mais valorizadas da Barra da Tijuca, no tradicional Condomínio Estrela do Mar, na ABM.
Localização privilegiada, próximo aos shoppings Downtown e Cittá América e praia, com serviço de ônibus para os principais pontos da cidade, direito ao clube privativo da ABM e balsa para praia. Prédio com portaria 24 horas, circuito interno de TV e infraestrutura de lazer completa com piscina, sauna, academia e salão de festas. Apartamento em andar alto, composto por sala em dois ambientes, varanda, dois quartos, sendo uma suíte, banheiro social, cozinha com armários, área de serviço e dependência completa de empregada. Uma vaga na escritura.

Localizado em rua residencial, tranquila e arborizada, com fácil acesso à Avenida das Américas e próximo aos principais pontos da região, como Shopping Downtown, Città Office Mall, supermercados, bancos, escolas, restaurantes, padarias e à Praia da Barra.

Além disso, o condomínio oferece excelente custo-benefício e acesso exclusivo ao Clube ABM, com diversas opções de lazer e esportes.

🚍 Diferenciais da ABM:
• Ônibus executivo para Centro, Zona Sul e diversos pontos da Barra
• Transporte circular para a estação de metrô
• Segurança e praticidade para o dia a dia

🏊 Infraestrutura de lazer completa:
• Piscina
• Academia
• Sauna
• Quadra esportiva
• Playground
• Salão de jogos
• Áreas de convivência

Uma excelente oportunidade para morar ou investir em uma das melhores localizações da Barra da Tijuca.

Agende sua visita e venha conhecer seu novo lar!`,
      fotos: [
        "assets/img/Estrela-mar/foto1 (1).jpeg",
        "assets/img/Estrela-mar/foto1 (2).jpeg",
        "assets/img/Estrela-mar/foto1 (3).jpeg",
        "assets/img/Estrela-mar/foto1 (4).jpeg",
        "assets/img/Estrela-mar/foto1 (5).jpeg",
        "assets/img/Estrela-mar/foto1 (6).jpeg",
        "assets/img/Estrela-mar/foto1 (7).jpeg",
        "assets/img/Estrela-mar/foto1 (8).jpeg",
        "assets/img/Estrela-mar/foto1 (9).jpeg",
        "assets/img/Estrela-mar/foto1 (10).jpeg",
        "assets/img/Estrela-mar/foto1 (11).jpeg",
        "assets/img/Estrela-mar/foto1 (12).jpeg",
        "assets/img/Estrela-mar/foto1 (13).jpeg",
        "assets/img/Estrela-mar/foto1 (14).jpeg",
        "assets/img/Estrela-mar/foto1 (15).jpeg",
        "assets/img/Estrela-mar/foto1 (16).jpeg",
        "assets/img/Estrela-mar/foto1 (17).jpeg",
        "assets/img/Estrela-mar/foto1 (18).jpeg",
        "assets/img/Estrela-mar/foto1 (19).jpeg",
        "assets/img/Estrela-mar/foto1 (20).jpeg",
      ],
    },

    {
      id: "morar-ou-investir",
      titulo: "Condomínio Mirante Cinco Estrelas",
      preco: "R$ 776.000",
      condicao: "Financiamento disponível",
      area: "63m²",
      quartos: "1",
      vagas: "1",
      bairro: "Barra da Tijuca",
      badge: "Oportunidade",
      diferenciais: [
        "Vista para a lagoa",
        "Varanda com cortina de vidro",
        "Ar-condicionado Split",
        "Armários planejados",
        "Piscina",
        "Quadra esportiva",
        "Academia",
        "Sauna",
        "Restaurante",
        "Portaria 24h",
        "Próximo ao Downtown",
      ],
      descricao: `Excelente apartamento FRONTAL com vista para a lagoa, totalmente equipado com armários e pronto para morar ou investir!

✔️ Planta inteligente: originalmente 1 quarto + 1 banheiro, reformado e hoje com 2 banheiros
✔️ Varanda com cortina de vidro e ar-condicionado
✔️ Ar-condicionado Split na sala e no quarto
✔️ Sol da manhã
✔️ Fechadura eletrônica
✔️ Quarto, banheiros e cozinha com armários planejados

🏢 Condomínio com infraestrutura completa:
Portaria 24h • 3 elevadores • 1 vaga de garagem • Piscina • Quadra esportiva • Academia • Sauna • Restaurante • Salão de beleza • Mercadinho

📍 Localização privilegiada:
Rua residencial, comércio próximo (Padaria Santa Marta, farmácia e hortifruti), próximo ao Shopping Downtown e Città América. Praia da Barra a menos de 20 minutos caminhando.

Ideal para quem busca praticidade, mobilidade, qualidade de vida e excelente potencial de investimento!`,
      fotos: [
        "assets/img/amoroso/amoroso (1).jpeg",
        "assets/img/amoroso/amoroso (2).jpeg",
        "assets/img/amoroso/amoroso (3).jpeg",
        "assets/img/amoroso/amoroso (4).jpeg",
        "assets/img/amoroso/amoroso (5).jpeg",
        "assets/img/amoroso/amoroso (6).jpeg",
        "assets/img/amoroso/amoroso (7).jpeg",
        "assets/img/amoroso/amoroso (8).jpeg",
        "assets/img/amoroso/amoroso (9).jpeg",
        "assets/img/amoroso/amoroso (10).jpeg",
        "assets/img/amoroso/amoroso (11).jpeg",
        "assets/img/amoroso/amoroso (14).jpeg",
        "assets/img/amoroso/amoroso (15).jpeg",
        "assets/img/amoroso/amoroso (16).jpeg",
      ],
    },

    {
      id: "apto-2q-pavuna",
      titulo: "Estação Zona Norte Madri",
      preco: "R$ 110.000",
      condicao: "FGTS e Financiamento",
      area: "46m²",
      quartos: "2",
      vagas: "0",
      bairro: "Pavuna",
      badge: null,
      diferenciais: [
        "2 quartos",
        "46m²",
        "Condomínio com lazer completo",
        "Desocupado",
        "Vista livre",
        "Próximo ao metrô",
        "Aceita financiamento e FGTS",
        "Isenção de IPTU e ITBI",
      ],
      descricao: `Apartamento 2 Quartos e Lazer Completo à Venda na Pavuna – Condomínio Madrid
Excelente oportunidade para quem busca praticidade, conforto e localização estratégica na Zona Norte do Rio de Janeiro!
Apartamento desocupado, com 46m², composto por uma sala confortável, bem ventilada e com ótima iluminação natural, 2 quartos aconchegantes, banheiro social, cozinha integrada à área de serviço e ambientes bem distribuídos para proporcionar mais conforto no dia a dia.
O imóvel possui piso em cerâmica em todos os ambientes, janelas em esquadria de alumínio, garantindo durabilidade, boa ventilação e fácil manutenção. Conta ainda com vista livre, proporcionando mais privacidade e uma agradável sensação de amplitude.
O condomínio Madrid – Estação Zona Norte oferece estrutura completa de lazer e segurança, com acesso digital, ambiente residencial organizado e familiar e baixo custo condominial.
Um dos grandes diferenciais é a localização: você estará a aproximadamente 400 metros do metrô, além de estar próximo a comércio, mercados, escolas, farmácias e diversas opções de transporte público.
Uma excelente opção para sair do aluguel e conquistar seu imóvel próprio com segurança, economia e qualidade de vida na Pavuna.

Agende sua visita!`,
      fotos: [
        "assets/img/madrid/madrid (7).jpg",
        "assets/img/madrid/madrid (2).jpg",
        "assets/img/madrid/madrid (3).jpg",
        "assets/img/madrid/madrid (4).jpg",
        "assets/img/madrid/madrid (5).jpg",
        "assets/img/madrid/madrid (6).jpg",
        "assets/img/madrid/madrid (1).jpg",
        "assets/img/madrid/madrid (8).jpg",
        "assets/img/madrid/madrid (9).jpg",
        "assets/img/madrid/madrid (10).jpg",
        "assets/img/madrid/madrid (11).jpg",
      ],
    },

    {
      id: "0022",
      titulo: "Lindo Apartamento Condomínio Blue Vision",
      preco: "R$ 898.000",
      condicao: "Apto para financiamento",
      area: "78m²",
      quartos: "2",
      vagas: "1",
      bairro: "Barra da Tijuca",
      badge: "Destaque",
      diferenciais: [
        "Piscina", "Academia", "Portaria 24h", "Salão de festas", "Churrasqueira",
        "Sauna", "Spa", "Varanda gourmet", "Quadra esportiva", "Espaço kids",
        "Armários planejados", "Ar-condicionado", "Documentação em dia",
        "Próximo à praia", "Elevador", "Segurança 24h", "Clube privativo na praia",
      ],
      descricao: `Encontre o apartamento dos seus sonhos na Barra da Tijuca! Este lindo apartamento no Condomínio Blue Vision oferece 78m² de puro conforto e lazer. Desfrute de 2 quartos, sendo 1 suíte com armários embutidos, sala climatizada com ar condicionado split e varanda integrada com cortina de vidro.
Aproveite a comodidade de 1 vaga de garagem na escritura.

O condomínio oferece infraestrutura completa para você e sua família, incluindo clube indoor com piscina aquecida, máquina de gelo, mercado pegue e pague, bicicletas compartilhadas, central de correspondência e estacionamento para visitantes. Além disso, aproveite o clube privativo na praia e a facilidade de transporte com ônibus e balsa para a praia, e ônibus direto para o metrô.

Apartamento pronto para morar te espera! Não perca esta oportunidade única de viver com conforto, segurança e lazer.

Agende agora mesmo a sua visita e realize o seu sonho!`,
      fotos: [
        "assets/img/0022/foto (1).jpeg",
        "assets/img/0022/foto (2).jpeg",
        "assets/img/0022/foto (3).jpeg",
        "assets/img/0022/foto (4).jpeg",
        "assets/img/0022/foto (5).jpeg",
        "assets/img/0022/foto (6).jpeg",
        "assets/img/0022/foto (7).jpeg",
        "assets/img/0022/foto (8).jpeg",
        "assets/img/0022/foto (9).jpeg",
        "assets/img/0022/foto (10).jpeg",
        "assets/img/0022/foto (11).jpeg",
        "assets/img/0022/foto (12).jpeg",
      ],
    },

    {
      id: "0023",
      titulo: "Casa em Condomínio em Vargem Pequena",
      preco: "R$ 1.050.000",
      condicao: "Apta para aceitar financiamento",
      area: "180m²",
      quartos: "3",
      vagas: "2",
      bairro: "Vargem Pequena",
      badge: "Destaque",
      diferenciais: [
        "Piscina", "Portaria 24h", "Salão de festas", "Churrasqueira", "Sauna",
        "Academia", "Quadra esportiva", "Espaço kids", "Documentação em dia",
        "Armários planejados", "Segurança 24h e monitoramento",
      ],
      descricao: `Descubra seu novo lar no Rio de Janeiro!

Viva com conforto e segurança nesta linda casa de condomínio em Vargem Pequena, Rio de Janeiro! Com 180m² de área útil, esta casa impecável oferece 3 quartos (2 suítes), varanda charmosa e acabamentos de qualidade. Desfrute de momentos inesquecíveis com a família e amigos na piscina e churrasqueira do condomínio. Possui ainda 1 lavabo, banheiro social e banheiro de serviço. Comodidade garantida com 2 vagas de estacionamento. Vista livre e localização privilegiada. Imóvel pronto para morar!

Lazer que o condomínio oferece: horta comunitária, playground, quadra poliesportiva, academia, salão de festas, piscina, sauna e churrasqueira.
Condomínio possui segurança 24 horas e monitoramento em todo condomínio.

Agende sua visita agora mesmo e realize o sonho da casa própria!`,
      fotos: [
        "assets/img/0023/foto (1).jpeg",
        "assets/img/0023/foto (2).jpeg",
        "assets/img/0023/foto (3).jpeg",
        "assets/img/0023/foto (4).jpeg",
        "assets/img/0023/foto (6).jpeg",
        "assets/img/0023/foto (7).jpeg",
        "assets/img/0023/foto (9).jpeg",
        "assets/img/0023/foto (10).jpeg",
        "assets/img/0023/foto (11).jpeg",
        "assets/img/0023/foto (12).jpeg",
        "assets/img/0023/foto (13).jpeg",
        "assets/img/0023/foto (14).jpeg",
        "assets/img/0023/foto (15).jpeg",
      ],
    },

    {
      id: "0024",
      titulo: "Condomínio Santa Marina",
      preco: "R$ 3.160.000",
      condicao: "Apta para financiamento",
      area: "450m²",
      quartos: "4",
      vagas: "2",
      bairro: "Barra da Tijuca",
      badge: "Destaque",
      diferenciais: ["Piscina", "Portaria 24h", "Churrasqueira", "Próximo à praia", "Segurança 24h", "Campo de futebol"],
      descricao: `450 m² de área construída
4 quartos, sendo 2 suítes
Ampla área social
Ambientes espaçosos e bem distribuídos
Excelente potencial para personalização
Localização privilegiada dentro do condomínio

🏘️ Condomínio Santa Marina

Segurança 24 horas
Ambiente familiar e tranquilo
Campo de futebol
Área de lazer para toda a família
Ruas arborizadas e excelente padrão residencial`,
      fotos: [
        "assets/img/0024/foto (1).jpeg",
        "assets/img/0024/foto (2).jpeg",
        "assets/img/0024/foto (3).jpeg",
        "assets/img/0024/foto (4).jpeg",
        "assets/img/0024/foto (5).jpeg",
        "assets/img/0024/foto (6).jpeg",
        "assets/img/0024/foto (7).jpeg",
        "assets/img/0024/foto (8).jpeg",
        "assets/img/0024/foto (9).jpeg",
        "assets/img/0024/foto (10).jpeg",
        "assets/img/0024/foto (11).jpeg",
        "assets/img/0024/foto (12).jpeg",
        "assets/img/0024/foto (13).jpeg",
        "assets/img/0024/foto (14).jpeg",
        "assets/img/0024/foto (15).jpeg",
        "assets/img/0024/foto (16).jpeg",
        "assets/img/0024/foto (17).jpeg",
        "assets/img/0024/foto (18).jpeg",
        "assets/img/0024/foto (19).jpeg",
        "assets/img/0024/foto (20).jpeg",
        "assets/img/0024/foto (21).jpeg",
        "assets/img/0024/foto (22).jpeg",
        "assets/img/0024/foto (23).jpeg",
      ],
    },

    {
      id: "0025",
      titulo: "Santa Monica Classic Houses",
      preco: "R$ 3.500.000",
      condicao: "À vista ou financiamento",
      area: "361m²",
      quartos: "4",
      vagas: "2",
      bairro: "Barra da Tijuca",
      badge: "Destaque",
      diferenciais: ["Piscina", "Academia", "Portaria 24h", "Churrasqueira", "Armários planejados", "Financiamento Caixa", "Documentação em dia"],
      descricao: `Descubra o privilégio de viver nesta magnífica casa duplex no cobiçado condomínio Santa Mônica Classic Houses, localizado na prestigiada Barra da Tijuca, Rio de Janeiro. Esta casa de condomínio oferece o melhor em conforto, segurança e lazer para sua família.

No pavimento superior, desfrute de 4 quartos, sendo 3 suítes amplas e elegantemente projetadas, garantindo máxima privacidade e bem-estar para todos. No andar térreo, você encontrará uma sofisticada biblioteca, perfeita para seus momentos de estudo ou trabalho, e três salas distintas, idealizadas para criar ambientes de convivência, entretenimento ou relaxamento.

A cozinha espaçosa e funcional atende todas as suas necessidades culinárias, complementada por uma dependência de serviço completa. A área externa é um verdadeiro oásis privativo, convidando você a relaxar na piscina, celebrar momentos especiais com amigos e família na churrasqueira e manter a forma em sua academia particular.

Com generosos 361 metros quadrados de área útil e 500 metros quadrados de área total, além de 2 vagas de garagem exclusivas, este imóvel foi pensado para seu conforto e comodidade. A ampla estrutura de banheiros, incluindo 2 banheiros sociais, 2 lavabos e 1 banheiro de serviço, oferece praticidade para moradores e visitantes.

Esta casa em condomínio na Barra da Tijuca é a oportunidade que você esperava para viver com qualidade de vida e exclusividade. Agende sua visita hoje mesmo e transforme este sonho em realidade. Entre em contato agora para mais informações.`,
      fotos: [
        "assets/img/0025/foto (1).jpg", "assets/img/0025/foto (2).jpg", "assets/img/0025/foto (3).jpg",
        "assets/img/0025/foto (4).jpg", "assets/img/0025/foto (5).jpg", "assets/img/0025/foto (6).jpg",
        "assets/img/0025/foto (7).jpg", "assets/img/0025/foto (8).jpg", "assets/img/0025/foto (9).jpg",
        "assets/img/0025/foto (10).jpg", "assets/img/0025/foto (11).jpg", "assets/img/0025/foto (12).jpg",
        "assets/img/0025/foto (13).jpg", "assets/img/0025/foto (14).jpg", "assets/img/0025/foto (15).jpg",
        "assets/img/0025/foto (16).jpg", "assets/img/0025/foto (17).jpg", "assets/img/0025/foto (18).jpg",
        "assets/img/0025/foto (19).jpg", "assets/img/0025/foto (20).jpg", "assets/img/0025/foto (21).jpg",
        "assets/img/0025/foto (22).jpg", "assets/img/0025/foto (23).jpg", "assets/img/0025/foto (24).jpg",
        "assets/img/0025/foto (25).jpg", "assets/img/0025/foto (28).jpg", "assets/img/0025/foto (30).jpg",
        "assets/img/0025/foto (31).jpg", "assets/img/0025/foto (32).jpg", "assets/img/0025/foto (33).jpg",
        "assets/img/0025/foto (34).jpg", "assets/img/0025/foto (35).jpg", "assets/img/0025/foto (36).jpg",
      ],
    },

  ], // fim prontos

  /* ══════════════════════════════════════════
     LANÇAMENTOS
  ══════════════════════════════════════════ */
  lancamentos: [
    {
  id: "claris",
  titulo: "Claris Casa & Clube | O privilégio de viver em um dos endereços mais exclusivos da Barra da Tijuca",
  preco: "R$ 4.950.000,00",
  condicao: "10/90 financiamento Bradesco",
  area: "292,66 a 580m²",
  quartos: "3 a 4 quartos",
  vagas: "2 a 3",
  bairro: "Barra da Tijuca",
  badge: "Novo",
  badgeClass: "etiqueta-ouro",
  diferenciais: ["Piscina", "Portaria 24h", "Academia", "Salão de festas", "Churrasqueira", "Playground", "Sauna", "Spa", "Espaço kids", "Quadra esportiva", "Varanda gourmet", "Próximo à praia", "Mercadinho", "Jardim privativo", "Segurança 24h"],
  descricao: `Em um cenário onde exclusividade, arquitetura contemporânea e qualidade de vida se encontram, nasce o Claris Casa & Clube, um empreendimento singular da Tegra Incorporadora, localizado no coração do Parque das Rosas, uma das regiões mais nobres e valorizadas da Barra da Tijuca.
Concebido para um público que valoriza privacidade, sofisticação e bem-estar, o Claris apresenta um conceito inédito de condomínio horizontal, reunindo residências de alto padrão em um ambiente cuidadosamente planejado para proporcionar conforto, segurança e uma experiência única de viver.

São apenas 99 residências exclusivas, desenvolvidas com arquitetura elegante e plantas inteligentes que variam de 318 m² a 580 m², além de opções com gardens privativos de 40 m² a 250 m². Espaços amplos, integrados e iluminados naturalmente valorizam cada ambiente, criando o equilíbrio perfeito entre funcionalidade e requinte.
Cada residência dispõe de 3 ou 4 suítes, living com integração aos ambientes externos, áreas gourmet, jardins privativos e acabamentos cuidadosamente planejados para proporcionar conforto absoluto e receber família e convidados com elegância.
Um dos diferenciais que reforçam a exclusividade do empreendimento é a garagem privativa no subsolo, com acesso individual para cada residência. Uma solução inovadora que garante mais segurança, privacidade e praticidade, preservando a estética do condomínio e valorizando ainda mais o projeto arquitetônico.

Um clube exclusivo para poucos
No Claris, o lazer foi concebido como uma extensão da sua casa.
Os moradores desfrutam de um clube privativo com infraestrutura completa, ambientes sofisticados e espaços dedicados ao bem-estar, ao esporte, à convivência e aos momentos inesquecíveis em família.
Cada detalhe foi pensado para oferecer uma rotina onde o tempo desacelera e a qualidade de vida se torna protagonista.

Natureza, sofisticação e localização privilegiada
Morar no Parque das Rosas significa viver cercado por áreas arborizadas, praças, ciclovias e toda a conveniência que tornou a Barra da Tijuca referência em qualidade de vida.
Em poucos minutos você encontra os melhores restaurantes, escolas, supermercados, centros médicos, shoppings, academias, além da praia da Barra e dos principais acessos da região.

Uma localização que oferece o privilégio de estar próximo de tudo, sem abrir mão da tranquilidade e da exclusividade.

Diferenciais que tornam o Claris único
Apenas 99 residências exclusivas;
Casas de 318 m² a 580 m²;
Opções com gardens privativos de até 250 m²;
Plantas com 3 e 4 suítes;
Arquitetura contemporânea e design sofisticado;
Living integrado aos ambientes externos;
Espaço gourmet e jardins privativos;
Garagem subterrânea exclusiva com acesso individual para cada residência;
Clube privativo de alto padrão;
Segurança 24 horas com tecnologia de última geração;
Condomínio fechado com infraestrutura completa;
Localização privilegiada no Parque das Rosas, um dos endereços mais desejados da Barra da Tijuca.
Mais do que uma casa. Um novo padrão de viver.

O Claris Casa & Clube foi concebido para quem entende que luxo vai além da estética. Está na privacidade de um condomínio exclusivo, na liberdade de viver cercado pela natureza, na tranquilidade de um ambiente seguro e na sofisticação presente em cada detalhe.

Um endereço raro, onde arquitetura, conforto, tecnologia e qualidade de vida se unem para proporcionar uma experiência incomparável.

Claris Casa & Clube. Viva o privilégio da exclusividade.

Agende uma apresentação exclusiva e descubra por que o Claris é um dos empreendimentos mais desejados da Barra da Tijuca.`,
  fotos: [
        "assets/img/claris/claris (1).jpg",
        "assets/img/claris/claris (2).jpg",
        "assets/img/claris/claris (3).jpg",
        "assets/img/claris/claris (4).jpg",
        "assets/img/claris/claris (5).jpg",
        "assets/img/claris/claris (6).jpg",
        "assets/img/claris/claris (7).jpg",
        "assets/img/claris/claris (8).jpg",
        "assets/img/claris/claris (9).jpg",
        "assets/img/claris/claris (10).jpg",
        "assets/img/claris/claris (11).jpg",
        "assets/img/claris/claris (12).jpg",
        "assets/img/claris/claris (13).jpg",
        "assets/img/claris/claris (14).jpg",
        "assets/img/claris/claris (15).jpg",
        "assets/img/claris/claris (16).jpg",
        "assets/img/claris/claris (17).jpg",
        "assets/img/claris/claris (18).jpg",
        "assets/img/claris/claris (19).jpg",
        "assets/img/claris/claris (20).jpg",
        "assets/img/claris/claris (21).jpg",
        "assets/img/claris/claris (22).jpg",
        "assets/img/claris/claris (23).jpg",
        "assets/img/claris/claris (24).jpg",
        "assets/img/claris/claris (25).jpg",
  ],
},

    {
  id: "symphony",
  titulo: "Symphony Residences",
  preco: "A partir R$ 890.000,00",
  condicao: "Com fluxo de pagamento",
  area: "62,20 a 194,26 m²",
  quartos: "1 a 3 quartos",
  vagas: "até 2 vagas",
  bairro: "Flamengo",
  badge: "Lançamento",
  badgeClass: "etiqueta-vermelho",
  diferenciais: ["Piscina", "Academia", "Portaria 24h", "Salão de festas", "Churrasqueira", "Playground", "Sauna", "Spa", "Espaço kids", "Quadra esportiva", "Varanda gourmet", "Ar-condicionado", "Financiamento Caixa", "Segurança 24h", "Elevador", "Mercadinho"],
  descricao: `Rua Marquês de Abrantes — Flamengo, Rio de Janeiro
Duas torres residenciais com apartamentos de 1, 2 e 3 quartos e coberturas duplex. Metragens de 62 m² a 111 m² (apartamentos tipo), com opções ainda maiores nas coberturas.
Unidades

Sala ampla, quartos bem iluminados, suíte (em opções selecionadas), cozinha funcional e infraestrutura para ar-condicionado split.
Lazer e serviços

Piscina semiolímpica (25 m), piscina infantil, spa, sauna, academia, espaço gourmet, salão de festas, coworking, brinquedoteca, sala gamer, playground e pet care.
Conveniências

Minimarket interno, espaço para delivery, bicicletário, vagas para carros e bikes elétricas, Wi-Fi nas áreas comuns e aplicativo de gestão condominial.
Segurança

Controle de acesso por biometria ou reconhecimento facial, CFTV, monitoramento perimetral e gerador para áreas essenciais.
Localização

No coração do Flamengo, próximo ao metrô, Aterro do Flamengo e Praia do Flamengo. Acesso facilitado ao Centro, Botafogo e Zona Sul. Supermercados, farmácias, escolas, restaurantes e hospitais a poucos minutos.`,
  fotos: [
        "assets/img/symphony/symphony (1).jpg",
"assets/img/symphony/symphony (2).jpg",
"assets/img/symphony/symphony (3).jpg",
"assets/img/symphony/symphony (4).jpg",
"assets/img/symphony/symphony (5).jpg",
"assets/img/symphony/symphony (6).jpg",
"assets/img/symphony/symphony (7).jpg",
"assets/img/symphony/symphony (8).jpg",
"assets/img/symphony/symphony (9).jpg",
"assets/img/symphony/symphony (10).jpg",
"assets/img/symphony/symphony (11).jpg",
"assets/img/symphony/symphony (12).jpg",
"assets/img/symphony/symphony (13).jpg",
"assets/img/symphony/symphony (14).jpg",
"assets/img/symphony/symphony (15).jpg",
"assets/img/symphony/symphony (16).jpg",
  ],
},


    {
      id: "breeze",
      titulo: "Breeze",
      preco: "Consulte",
      condicao: "Fluxo de pagamento",
      area: "120m²",
      quartos: "2 a 3",
      vagas: "1",
      bairro: "Barra Olímpica",
      badge: "Lançamento",
      diferenciais: ["Deck molhado com solário", "Pool house e pool bar", "Playground", "Churrasqueira", "Pet place", "Quadra poliesportiva"],
      descricao: `Um lugar onde o BREEZE nasce como um respiro. E é mais do que morar: é viver com luz, com ar, com horizonte. É trocar ruído por calma, checklists por rituais, pressa por presença. Não prometemos mais horas. Prometemos horas melhores. Mais intensas. Mais leves. Mais suas. Se a cidade pede fôlego, aqui você encontra o seu lugar. Um endereço pensado para quem deseja redescobrir a vida em outro ritmo na Barra.`,
      fotos: [
        "assets/img/breeze/foto (1).jpg", "assets/img/breeze/foto (2).jpg", "assets/img/breeze/foto (3).jpg",
        "assets/img/breeze/foto (4).jpg", "assets/img/breeze/foto (5).jpg", "assets/img/breeze/foto (6).jpg",
        "assets/img/breeze/foto (7).jpg", "assets/img/breeze/foto (8).jpg", "assets/img/breeze/foto (9).jpg",
        "assets/img/breeze/foto (10).jpg", "assets/img/breeze/foto (11).jpg", "assets/img/breeze/foto (12).jpg",
        "assets/img/breeze/foto (13).jpg", "assets/img/breeze/foto (14).jpg", "assets/img/breeze/foto (15).jpg",
        "assets/img/breeze/foto (16).jpg", "assets/img/breeze/foto (17).jpg", "assets/img/breeze/foto (18).jpg",
        "assets/img/breeze/foto (19).jpg", "assets/img/breeze/foto (20).jpg", "assets/img/breeze/foto (21).jpg",
      ],
    },

    {
      id: "grand-quartier",
      titulo: "Grand Quartier",
      preco: "Consulte",
      condicao: "Financiamento disponível",
      area: "120m²",
      quartos: "3",
      vagas: "2",
      bairro: "Barra Olímpica",
      badge: "Lançamento",
      diferenciais: ["Piscina aquecida", "Spa", "Espaço kids", "Portaria 24h", "Academia", "Churrasqueira"],
      descricao: `Sofisticado apartamento no Grand Quartier, empreendimento de luxo com portaria 24h, piscina aquecida, spa e espaço kids. Planta generosa com ambientes integrados, suíte máster com closet e varanda ampla. Localização privilegiada na Barra da Tijuca com fácil acesso a todas as principais vias da cidade.`,
      fotos: [
        "assets/img/quartier/quartier1.jpg",
        "assets/img/quartier/quartier2.jpg",
        "assets/img/quartier/quartier3.jpg",
        "assets/img/quartier/quartier4.jpg",
      ],
    },

    {
      id: "bio-recreio",
      titulo: "Bio Recreio Residences",
      preco: "A partir de R$ 590.000",
      condicao: "Parcelamento direto com a incorporadora",
      area: "180 a 189m²",
      quartos: "0",
      vagas: "0",
      bairro: "Recreio dos Bandeirantes",
      badge: "Lançamento",
      diferenciais: ["Área verde preservada", "Lotes amplos", "Infraestrutura completa", "Segurança 24h", "Paisagismo exclusivo", "Murado"],
      descricao: `Loteamento de alto padrão em área verde preservada no coração do Recreio dos Bandeirantes. Lotes espaçosos com infraestrutura completa (água, luz, esgoto, asfalto), segurança 24h e paisagismo exclusivo. Oportunidade única de construir o projeto dos seus sonhos em uma das regiões mais valorizadas do Rio de Janeiro.`,
      fotos: [
        "assets/img/bio/bio1.jpg",
        "assets/img/bio/bio2.jpg",
        "assets/img/bio/bio3.jpg",
        "assets/img/bio/bio4.jpg",
      ],
    },

    {
      id: "onlife-taquara",
      titulo: "Onlife",
      preco: "Consulte condições",
      condicao: "MCMV / Financiamento facilitado",
      area: "40 a 55m²",
      quartos: "1 a 2",
      vagas: "1",
      bairro: "Taquara",
      badge: "Lançamento",
      diferenciais: ["MCMV", "Financiamento facilitado", "Fácil acesso", "Academia", "Salão de festas", "Portaria inteligente"],
      descricao: `Empreendimento moderno na Estrada dos Bandeirantes, com fácil acesso a toda a Zona Oeste do Rio. Unidades compactas e inteligentes, ideais para quem busca praticidade e qualidade de vida. Infraestrutura de lazer, portaria inteligente e excelente localização. Enquadrado no programa Minha Casa Minha Vida com financiamento facilitado.`,
      fotos: [
        "assets/img/onlife/onlife1.jpg",
        "assets/img/onlife/onlife2.jpg",
        "assets/img/onlife/onlife3.jpg",
        "assets/img/onlife/onlife4.jpg",
      ],
    },

    {
      id: "lanai-pontal",
      titulo: "Lanai Pontal Oceânico",
      preco: "A partir de R$ 580.000",
      condicao: "Financiamento Caixa",
      area: "62 a 87m²",
      quartos: "2 a 3",
      vagas: "1 a 2",
      bairro: "Recreio dos Bandeirantes",
      badge: "Lançamento",
      diferenciais: ["Vista para o mar", "Gardens disponíveis", "Piscina", "Academia", "Salão de jogos", "Próximo à praia do Pontal"],
      descricao: `Viva com o mar sempre presente. Gardens e apartamentos com amplas varandas a metros da praia do Pontal. Clube completo com piscina, academia, salão de jogos e muito mais. Unidades de 2 e 3 quartos com plantas inteligentes e acabamento de qualidade. Uma das últimas oportunidades de morar à beira-mar no Recreio.`,
      fotos: [
        "assets/img/lanai/lanai2.jpg",
        "assets/img/lanai/lanai3.jpg",
        "assets/img/lanai/lanai4.jpg",
        "assets/img/lanai/lanai1.jpg",
      ],
    },

    {
      id: "living-vista-parque",
      titulo: "Living Vista Parque",
      preco: "A partir de R$ 420.000",
      condicao: "Financiamento disponível",
      area: "37 a 67m²",
      quartos: "2 a 3",
      vagas: "1",
      bairro: "Jacarepaguá",
      badge: "Lançamento",
      diferenciais: ["Vista para o Parque", "Portaria inteligente", "Academia", "Piscina", "Salão de festas", "Acabamento contemporâneo"],
      descricao: `Apartamentos modernos com vista privilegiada para o Parque Estadual da Pedra Branca. Infraestrutura de lazer completa com piscina, academia e salão de festas. Portaria inteligente e acabamento contemporâneo. Localização estratégica em Jacarepaguá, com fácil acesso à Barra da Tijuca e ao Centro.`,
      fotos: [
        "assets/img/living/living1.jpg",
        "assets/img/living/living2.jpg",
        "assets/img/living/living3.jpg",
        "assets/img/living/living4.jpg",
      ],
    },

  ], // fim lancamentos

}; // fim IMOVEIS


/* ─── HELPERS usados por ambas as páginas ─── */

/** Converte array de prontos+lancamentos em dicionário {id: imovel} */
function buildDB(catalogo) {
  const db = {};
  [...catalogo.prontos, ...catalogo.lancamentos].forEach(im => {
    // gera link automaticamente a partir do id
    im.link = `https://vinutto.com/imovel.html?id=${im.id}`;
    // badgeClass para imovel.html
    im.badgeClass = im.badge === 'Lançamento' ? 'badge-red' : 'badge-gold';
    db[im.id] = im;
  });
  return db;
}

const IMOVEIS_DB = buildDB(IMOVEIS);

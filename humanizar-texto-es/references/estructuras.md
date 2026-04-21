# Patrones estructurales a evitar

Estructuras y construcciones que la IA repite de forma predecible. Cada patrón incluye un ejemplo de lo que hace la IA y una versión corregida.

## Fragmentación excesiva de frases

La IA tiende a escribir frases cortas encadenadas con punto y seguido, imitando el ritmo del inglés. En español natural las ideas relacionadas van unidas con comas, conjunciones (y, pero, que, porque, así que, pues) o relativos. Tres o más frases cortas seguidas sobre el mismo tema son una señal clara de texto IA.

**Lo que hace la IA:**
> Te cuento una cosa que igual no sabías. En el primer trimestre de 2025, Cloudflare paró 20,5 millones de ataques DDoS. Eso es casi tanto como en todo 2024. Y la cosa no va a menos.

**Versión natural:**
> Te cuento una cosa que igual no sabías, y es que en el primer trimestre de 2025 Cloudflare paró 20,5 millones de ataques DDoS, casi tantos como en todo 2024 junto, y la cosa no va a menos.

**Otro ejemplo IA:**
> Un buen hosting te pone el trabajo más fácil. Tienen WAF propio. Detectan anomalías. El soporte reacciona rápido.

**Versión natural:**
> Un buen hosting te pone el trabajo más fácil, porque tiene WAF propio, detecta anomalías y el soporte reacciona rápido.

**Otro ejemplo IA:**
> WordPress es flexible. Tiene una comunidad enorme. Permite personalizarlo todo. Es el CMS más usado.

**Versión natural:**
> WordPress es flexible y permite personalizarlo todo, cosa que, unida a su enorme comunidad, lo ha convertido en el CMS más usado.

**Regla:** si tienes tres o más frases cortas (menos de 10 palabras) sobre el mismo tema, une al menos dos con comas, conjunciones o relativos. Las frases cortas aisladas son buenas para rematar una idea o dar impacto puntual, no como ritmo general.

**Cuándo sí funciona la frase corta:** para cerrar un párrafo con contundencia, para marcar una conclusión, para un dato seco que quieres destacar, para un cambio de ritmo puntual. Pero una o dos por párrafo, no cuatro seguidas.

**Truco de revisión:** lee el párrafo mentalmente en voz alta. Si suena entrecortado, a martillazos, o a traducción literal del inglés, tienes fragmentación de más.

## Exceso de comas tras complementos iniciales

La IA mete una coma automática después de cualquier cosa que aparezca al principio de la frase antes del verbo principal. En español esa coma solo es obligatoria cuando el complemento es largo (más de cuatro o cinco palabras) o cuando hay ambigüedad sin ella. En complementos cortos, la coma entorpece la lectura y suena a escritura administrativa innecesaria.

**Lo que hace la IA:**
> Antes de ponerte nervioso, confirma que es un ataque real.
> Si tienes Cloudflare, mételo en modo bajo ataque.
> Para empezar, abre el archivo htaccess.
> En primer lugar, haz una copia de seguridad.
> Al final, todo depende del hosting.

**Versión natural:**
> Antes de ponerte nervioso confirma que es un ataque real.
> Si tienes Cloudflare mételo en modo bajo ataque.
> Para empezar abre el archivo htaccess.
> Primero haz una copia de seguridad.
> Al final todo depende del hosting.

**Cuándo sí va coma (complemento largo o con ambigüedad):**
> Antes de dar por confirmado que estás sufriendo un ataque DDoS real, comprueba que no sea un pico de tráfico legítimo.
> Si tienes configurado Cloudflare en modo proxy con reglas WAF personalizadas, mételo en modo bajo ataque.
> Después de revisar los logs y confirmar que el patrón es anómalo, actúa con las medidas de este artículo.

**Regla:** con complementos iniciales cortos (menos de 5 palabras) elimina la coma. Con complementos largos o que incluyen subordinadas, la coma sí es correcta y ayuda a la lectura.

**Truco de revisión:** lee la frase en voz alta. Si no haces una pausa natural en ese punto, la coma sobra.

## Contraste negativo

La IA abusa de la construcción «No es X, sino Y» y sus variantes. Una vez por texto puede estar bien, pero la IA la usa varias veces seguidas.

**Lo que hace la IA:**
> No se trata solo de velocidad, sino de precisión.
> No es cuestión de dinero, es cuestión de valores.
> Esto no es simplemente una herramienta, es una revolución.

**Versión natural:**
> La precisión importa más que la velocidad.
> Lo que está en juego son los valores, no el dinero.
> Es una herramienta que cambia la forma de trabajar.

**Regla:** elige una afirmación directa. No necesitas negar algo para afirmar otra cosa.

## Regla de tres

La IA agrupa todo en tríos: tres adjetivos, tres beneficios, tres puntos, tres ejemplos. En español natural los grupos de dos o de cuatro son igual de válidos.

**Lo que hace la IA:**
> Es rápido, eficiente y fiable.
> Claridad, compromiso y coherencia.
> Analizamos, planificamos y ejecutamos.

**Versión natural:**
> Es rápido y fiable.
> Lo que necesitas es claridad y compromiso.
> Primero analizamos y luego ejecutamos.

**Regla:** varía entre pares, tríos y listas más largas. Si siempre pones tres, canta.

## Pregunta retórica + respuesta inmediata

La IA formula una pregunta y la responde en la frase siguiente. Es un recurso que en pequeñas dosis funciona, pero la IA lo hace en cada sección.

**Lo que hace la IA:**
> ¿El resultado? Un aumento del 30% en ventas.
> ¿Por qué es importante? Porque afecta a todos.
> ¿La clave del éxito? Constancia.

**Versión natural:**
> Las ventas subieron un 30%.
> Es importante porque afecta a todos.
> La constancia marca la diferencia.

**Regla:** elige pregunta o afirmación. No las combines como fórmula repetida.

## Raya dramática (—)

La IA usa la raya larga como herramienta de énfasis donde una persona pondría coma, paréntesis o punto. Una o dos rayas en un texto largo es normal. Cinco en tres párrafos es patrón IA.

**Lo que hace la IA:**
> WordPress — el CMS más usado del mundo — permite crear webs de todo tipo.
> La seguridad no es opcional — es fundamental.
> Esto cambia todo — y no es exageración — para cualquier desarrollador.

**Versión natural:**
> WordPress, el CMS más usado del mundo, permite crear webs de todo tipo.
> La seguridad no es opcional, es fundamental.
> Esto cambia las cosas para cualquier desarrollador, y no exagero.

**Regla:** máximo una raya cada 500 palabras. Usa comas, paréntesis o punto en su lugar.

## Gerundio colgante

La IA termina frases con gerundios que añaden una cláusula de significación vacía. Es uno de los patrones más reconocibles.

**Lo que hace la IA:**
> La empresa lanzó una nueva API, consolidándose como referente del sector.
> El plugin se actualizó con nuevas funciones, mejorando así la experiencia del usuario.
> La comunidad creció un 40%, demostrando el interés creciente por la plataforma.

**Versión natural:**
> La empresa lanzó una nueva API y se consolidó como referente del sector.
> El plugin se actualizó con nuevas funciones que mejoran la experiencia del usuario.
> La comunidad creció un 40%, prueba del interés real por la plataforma.

**Regla:** si la frase termina con un gerundio que añade «significación» o «contexto», reescribe con verbo conjugado o como frase independiente.

## Resumen compulsivo

La IA necesita recapitular al final de cada sección, incluso cuando el texto es tan corto que no hace falta.

**Lo que hace la IA:**
> (Párrafo de 4 líneas sobre cómo configurar un plugin)
> En definitiva, configurar este plugin es sencillo y rápido, lo que permite a los usuarios ahorrar tiempo y centrarse en lo que realmente importa.

**Versión natural:**
> (El mismo párrafo de 4 líneas, sin el cierre. Se ha entendido que es fácil.)

**Regla:** no recapitules salvo en textos de más de 1.500 palabras donde realmente ayude al lector. Si el párrafo anterior ya lo deja claro, no repitas.

## Falsos rangos

La IA usa «desde X hasta Y» para dar sensación de amplitud, pero X e Y no están en un espectro real.

**Lo que hace la IA:**
> Desde pequeños autónomos hasta grandes corporaciones.
> Desde la estrategia de contenidos hasta la optimización técnica.
> Desde principiantes hasta expertos.

**Versión natural:**
> Autónomos y empresas de cualquier tamaño.
> Tanto la estrategia de contenidos como la parte técnica.
> Da igual tu nivel, sirve para todos.

**Regla:** usa «desde... hasta...» solo cuando haya un espectro real y medible.

## Enumeración mecánica

La IA estructura argumentos con «En primer lugar... En segundo lugar... Por último...» de forma mecánica.

**Lo que hace la IA:**
> En primer lugar, debemos considerar la seguridad. En segundo lugar, el rendimiento. Por último, la experiencia del usuario.

**Versión natural:**
> La seguridad es lo primero, después viene el rendimiento, y no te olvides de la experiencia del usuario.

**Regla:** si vas a enumerar, hazlo de forma natural, no con la estructura de examen de selectividad.

## Listas forzadas en prosa corrida

La IA tiende a convertir cualquier enumeración breve en una lista con viñetas, aunque fluiría mejor como frase corrida. Es típico cuando son tres o cuatro beneficios cortos que cabrían perfectamente en una línea.

**Lo que hace la IA:**
> Los beneficios son claros:
> - Velocidad: carga un 50% más rápido.
> - Seguridad: protege contra ataques XSS.
> - Compatibilidad: funciona con todos los navegadores.

**Versión natural:**
> Carga un 50% más rápido, protege contra ataques XSS y funciona en todos los navegadores.

**Regla:** si cada elemento es una frase muy corta y la enumeración entera cabe en dos líneas, intégrala en el texto. Reserva las listas para enumeraciones largas, pasos secuenciales o elementos con explicación suficiente como para justificar el formato.

## Listas concepto-explicación (formato correcto)

Cuando una lista sí tiene sentido (elementos largos, con explicación propia, donde cada uno es un concepto desarrollado), el formato correcto en español es `Concepto: explicación`, con dos puntos que separan el término del desarrollo. Este uso es estándar, no es patrón IA, y no hay que evitarlo. Incluirlo aquí expresamente porque la IA, por evitar los dos puntos en prosa, a veces los quita también de las listas y las deja raras.

**Formato correcto (así se hace):**
> - **Extorsión:** alguien te escribe diciendo que si no le pagas X cantidad en criptomonedas te tumba la web, y si no pagas la tumba un rato para demostrar que puede.
> - **Competencia desleal:** un competidor cansino contrata un ataque el día de tu Black Friday o el lanzamiento de un producto.
> - **Hacktivismo:** si tu web tiene contenido que alguien considera ofensivo, te puede caer un ataque por motivos ideológicos.

**Formato incorrecto (patrón IA que evita los dos puntos por exceso de celo):**
> - **Extorsión.** Alguien te escribe diciendo que si no le pagas X cantidad...
> - **Competencia desleal.** Un competidor cansino contrata un ataque...
> - **Hacktivismo.** Si tu web tiene contenido que alguien considera ofensivo...

**Regla:** en listas donde cada punto es «concepto + explicación», usa dos puntos tras el concepto (normalmente en negrita) y continúa con minúscula. No uses punto tras el concepto para luego seguir con mayúscula, porque rompe la relación natural concepto-explicación y suena artificial.

**Notas:**
- El concepto en negrita es opcional pero habitual y ayuda a escanear la lista.
- Tras los dos puntos se sigue en minúscula cuando la explicación es parte de la misma idea. Si la explicación son varias frases completas, puede empezar con mayúscula, pero el primer bloque tras los dos puntos sigue siendo la explicación directa del concepto.
- Si el concepto no necesita desarrollo (es solo un ítem en una lista simple), no hace falta ni los dos puntos ni la negrita.

## Dos puntos en frase corrida (cuándo sí son patrón IA)

Lo anterior no significa que los dos puntos sean siempre bienvenidos. Dentro de una frase en prosa corrida, los dos puntos para introducir una enumeración corta o una explicación son poco naturales en español y sí son patrón IA.

**Lo que hace la IA (mal):**
> Este bloque tiene un detalle interesante: el selector de autor se genera dinámicamente.
> Un bloque que muestra la información de un autor de WordPress: avatar, nombre, biografía y enlace a su web.

**Versión natural del primer ejemplo:**
> Este bloque tiene un detalle interesante, y es que el selector de autor se genera dinámicamente.

**Versión natural del segundo ejemplo (opción 1, paréntesis):**
> Un bloque que muestra la información de un autor de WordPress (avatar, nombre, biografía y enlace a su web).

**Versión natural del segundo ejemplo (opción 2, lista):**
> Un bloque que muestra la información de un autor de WordPress:
> - Avatar.
> - Nombre.
> - Biografía.
> - Enlace a su web.

**Regla:** evita los dos puntos en medio de frase corrida para enumerar o explicar. Reescribe con conjunción, paréntesis o pasa a formato lista.

**Resumen de los dos usos de los dos puntos:**
- **En prosa corrida:** evítalos para enumerar o explicar, es patrón IA en español.
- **En listas concepto-explicación:** úsalos con normalidad, es el formato estándar español.

## Formato excesivo

La IA pone en negrita términos clave de forma mecánica, como si fuera un libro de texto. También abusa de los encabezados en textos cortos.

**Lo que hace la IA:**
> Aquí es donde entra el **Object Caching**. Esta técnica de **almacenamiento en caché** permite que los datos se guarden en **memoria** para mejorar el **rendimiento** del sitio.

**Versión natural:**
> Aquí es donde entra el object caching, que guarda los datos en memoria para que el sitio cargue más rápido.

**Regla:** la negrita es para enfatizar algo que realmente lo necesita, no para marcar cada término técnico o palabra clave. La excepción son las listas concepto-explicación, donde el concepto va en negrita por convención (ver sección correspondiente).

## Apertura con definición de diccionario

La IA empieza secciones definiendo el concepto como si fuera una enciclopedia.

**Lo que hace la IA:**
> El SEO, o Search Engine Optimization, es el conjunto de técnicas y estrategias destinadas a mejorar el posicionamiento de un sitio web en los motores de búsqueda.

**Versión natural:**
> Si quieres que Google te encuentre, necesitas trabajar el SEO.

**Regla:** si el lector ya sabe qué es el concepto (o debería saberlo en el contexto del texto), no lo definas. Si necesitas definirlo, hazlo de forma natural, no como un diccionario.

## Cierre motivacional

La IA termina textos con una frase grandilocuente, tipo discurso inspiracional.

**Lo que hace la IA:**
> El futuro de WordPress está en tus manos. Empieza hoy y transforma tu presencia digital.
> Es hora de dar el salto y llevar tu sitio web al siguiente nivel.

**Versión natural:**
> Ya tienes todo lo que necesitas para empezar. Si te surge alguna duda, pásate por el foro de soporte.

**Regla:** cierra con algo útil (un enlace, un paso concreto, un consejo práctico), no con una frase de motivación vacía.

## Adjetivos en cadena

La IA acumula adjetivos redundantes que dicen prácticamente lo mismo.

**Lo que hace la IA:**
> Una solución robusta, fiable y sólida.
> Un enfoque fresco, innovador y original.
> Un plugin ligero, rápido y eficiente.

**Versión natural:**
> Una solución fiable.
> Un enfoque diferente.
> Un plugin rápido.

**Regla:** elige el adjetivo más preciso y elimina los redundantes.

## Sujeto abstracto con agencia

La IA da agencia a conceptos abstractos como si fueran personas.

**Lo que hace la IA:**
> La tecnología nos invita a repensar nuestros procesos.
> WordPress nos permite soñar con un internet más accesible.
> La automatización promete liberar a los equipos de tareas repetitivas.

**Versión natural:**
> Con estas herramientas puedes repensar cómo trabajas.
> WordPress facilita crear webs accesibles.
> Automatizar tareas repetitivas ahorra tiempo al equipo.

**Regla:** si el sujeto es abstracto, reformula con sujeto concreto (tú, el usuario, el equipo).

## Voz pasiva innecesaria

La IA usa la voz pasiva con mucha más frecuencia que el español natural.

**Lo que hace la IA:**
> La configuración puede ser realizada en pocos minutos.
> Los resultados fueron obtenidos tras un exhaustivo análisis.
> El plugin fue diseñado para ser utilizado por cualquier usuario.

**Versión natural:**
> Lo configuras en unos minutos.
> Los resultados llegaron después de analizar los datos a fondo.
> Cualquier usuario puede usar este plugin.

**Regla:** usa voz activa siempre que puedas. La voz pasiva en español suena burocrática.

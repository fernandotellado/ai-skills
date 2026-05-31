---
name: humanizar-texto-es
description: "Elimina patrones de escritura típicos de IA en textos en español de España para que suenen naturales y humanos. Aplica esta skill siempre que generes, edites o revises textos en español: artículos, guías, tutoriales, emails, copy comercial, publicaciones en redes sociales, documentación, informes o cualquier prosa. Actívala también cuando el usuario pida humanizar texto, eliminar tono robótico, mejorar la naturalidad de un texto, o cuando mencione que algo suena a IA, a ChatGPT o a texto generado automáticamente."
license: GPL-2.0-or-later
compatibility: "Cualquier asistente de IA que genere texto en español de España. Compatible con Claude, ChatGPT, Gemini y otros LLM."
metadata:
  author: fernando-tellado
  version: "1.0"
  language: es-ES
---

# Humanizar texto en español

Elimina los patrones predecibles de la escritura generada por IA para que los textos en español de España suenen naturales, directos y escritos por una persona real.

## Cuándo usar esta skill

Úsala siempre que:

- Generes texto en español (artículos, guías, tutoriales, emails, copy, redes sociales)
- Edites o revises borradores para eliminar el tono artificial
- El usuario diga que algo "suena a IA", "suena robótico" o "no parece natural"
- Necesites adaptar texto generado a un estilo conversacional y cercano
- Escribas para audiencias de España (español peninsular)

## Principios fundamentales

### El mantra anti-IA

```
Escribe como hablas, no como un manual.
Di las cosas una vez, con claridad.
Confía en el lector: no le expliques lo obvio.
Varía el ritmo: mezcla frases cortas con largas.
```

Eso sí, *natural* no es lo mismo que *coloquial*. Que un texto suene a persona no significa meter jerga: el grado de cercanía depende del tipo de texto (ver «Adaptación por tipo de texto»). Un informe o una documentación técnica pueden ser naturales sin un solo «mola».

### Reglas principales

1. **Elimina frases de relleno.** Quita aperturas predecibles y muletillas de énfasis. Consulta la sección "Palabras y frases de riesgo".
2. **Rompe las estructuras formulaicas.** Evita contrastes binarios, tríos forzados, preguntas retóricas con respuesta inmediata. Consulta la sección "Patrones estructurales a evitar".
3. **Entrelaza frases cortas en español.** Tres o más frases cortas seguidas (de menos de ~10 palabras) sobre el mismo tema son ritmo de inglés, no de español. Une con comas, conjunciones (y, pero, porque, así que) o relativos. Pero sin pasarte: unir de más crea frases-río, otro patrón de IA. La meta es alternar largas y cortas, no saltar de un extremo a otro.
4. **No pongas coma automática tras complementos iniciales cortos.** Con complementos de 4 palabras o menos, no va coma. Solo va con 5 palabras o más, o si hay ambigüedad sin ella.
5. **Varía el ritmo y los grupos.** Mezcla la longitud de las frases. No agrupes siempre en tres: a veces dos, a veces cuatro, a veces una lista. Lo que canta es repetir siempre el mismo número. No termines todos los párrafos igual.
6. **Confía en el lector.** Afirma directamente. No suavices, no justifiques, no lleves de la mano.
7. **Elimina frases de impacto artificial.** Si suena a titular de LinkedIn o a frase para enmarcar, reescríbela.
8. **Usa español de España, con el registro que pida el texto.** Nada de español neutro ni latinoamericanismos formales. En artículos, tutoriales y redes, tutea y usa expresiones cercanas sin miedo. En informes, documentación o correo formal, mantente natural pero sobrio: la cercanía es el techo, no una obligación, y a veces toca usted. Ajusta el grado de coloquialismo al género (ver «Adaptación por tipo de texto»).

## Comprobaciones rápidas

Antes de entregar cualquier texto, revisa:

- ¿Hay tres o más frases cortas seguidas (de menos de ~10 palabras) sobre el mismo tema? Une al menos dos con comas, conjunciones o relativos. (Pero no las encadenes todas: una frase-río también canta.)
- ¿Hay coma después de un complemento inicial corto (4 palabras o menos)? Quítala. Solo va con 5 palabras o más, o si hay ambigüedad.
- ¿Tres frases seguidas tienen la misma longitud? Rompe una.
- ¿Un párrafo termina con frase sentenciosa tipo frase célebre? Cámbiala.
- ¿Hay una raya (—) antes de una revelación? Quítala, usa coma o punto.
- ¿Estás explicando una metáfora después de usarla? Confía en que se entiende.
- ¿Empiezas con "En el mundo actual..." o similar? Bórralo y empieza por lo que importa.
- ¿Hay más de dos conectores formales (asimismo, no obstante, por consiguiente) en un párrafo? Sustituye la mayoría por conexiones naturales.
- ¿Terminas una sección con "En definitiva" o "En resumen"? Elimínalo.
- ¿Usas gerundios vacíos al final de la frase ("contribuyendo a...", "posicionándose como...")? Reescribe con verbo conjugado.
- ¿Hay dos puntos (:) en medio de una frase corrida para introducir una enumeración corta? Reescribe con coma o paréntesis. (Ojo: en listas concepto-explicación tipo `Concepto: explicación`, los dos puntos sí son correctos y no hay que quitarlos.)
- ¿Usas el punto y coma (;) para encadenar frases? Suele ir mejor coma, punto o «y». Déjalo solo en sus usos legítimos (enumeraciones complejas, dos oraciones muy ligadas).
- ¿Abres o cierras con un gancho de intriga («lo que nadie te cuenta», «y aquí viene lo bueno», «spoiler:»)? Quítalo y ve al dato. Lo mismo con la escena hipotética («imagina que…») y la empatía fingida («sé lo que estás pensando», «no estás solo»).
- ¿Hay un calco del inglés («cuando se trata de», «al final del día», «asegúrate de»)? Cámbialo por su forma natural.
- ¿El titular promete de más («todo lo que necesitas saber», «guía definitiva»)? Concrétalo.
- ¿El titular está con mayúscula inicial en cada palabra (estilo inglés) o, al revés, todo en minúscula? En español solo van en mayúscula la primera letra y los nombres propios.

## Palabras y frases de riesgo

La fuente completa, con todos los matices, es [references/palabras.md](references/palabras.md); esto es solo un resumen. Incluye:

- **Palabras infladas**: panorama, ecosistema, paradigma, sinergia, catalizador, implementar, optimizar, potenciar, fomentar, abordar, brindar, garantizar, innovador
- **Muletillas de apertura**: "En el mundo actual...", "Cabe destacar que...", "Es importante señalar que..."
- **Muletillas de cierre**: "En definitiva", "En resumen", "En conclusión"
- **Conectores formales excesivos**: asimismo, no obstante, por consiguiente, en este sentido
- **Calcos del inglés**: "cuando se trata de", "al final del día", "asegúrate de", "en términos de", accionable
- **Verbos de elevación**: sumérgete, embárcate, elevar, llevar al siguiente nivel, abrazar el cambio
- **Cuantificadores vagos**: diversos, múltiples, una serie de, un sinfín de, diferentes
- **Metáforas gastadas**: pilar fundamental, piedra angular, motor de cambio, tejido social, hoja de ruta
- **Inflación de importancia**: transformador, revolucionario, sin precedentes, referente, disruptivo
- **Palabras de prohibición absoluta** (esta lista sí es cerrada): iterar, iteración, fricción, granular, granularidad, epítome, inquebrantable, vibrante, crucial, indeleble, desbloquear
- **De uso recurrente** (modera, no las elimines: son válidas en su contexto): solución, experiencia, ideal, perfecto, potente, clave, realmente, simplemente

## Patrones estructurales a evitar

Consulta la lista completa con ejemplos en [references/estructuras.md](references/estructuras.md). Los más frecuentes:

- **Contraste negativo**: "No se trata solo de X, sino de Y" / "No es X, es Y"
- **Regla de tres**: siempre agrupar en tripletas de adjetivos, sustantivos o verbos
- **Pregunta retórica + respuesta**: "¿El resultado? Evidente."
- **Raya dramática**: uso de — para dar énfasis donde iría coma o punto
- **Gerundio colgante**: terminar frases con gerundios vacíos de significado
- **Resumen compulsivo**: recapitular al final de cada sección o párrafo
- **Falsos rangos**: "desde X hasta Y" cuando X e Y no forman un espectro real
- **Enumeración mecánica**: "En primer lugar... En segundo lugar... Por último..."
- **Capitalización de titulares**: mayúscula en cada palabra al estilo inglés (o, al corregir, el titular entero en minúscula)
- **Punto y coma en exceso**: usar el ; para encadenar frases donde iría coma, punto o «y»

## Ganchos y fórmulas de relleno

La IA crea intriga, finge cercanía y rellena transiciones con fórmulas muy reconocibles. Lista completa con ejemplos en [references/ganchos.md](references/ganchos.md):

- **Intriga / clickbait**: "lo que nadie te cuenta", "y aquí viene lo bueno", "spoiler:", "sigue leyendo", "¿la mejor parte?"
- **Storytelling forzado**: "imagina que…", "son las 3 de la mañana y…", "déjame que te cuente una historia"
- **Empatía simulada**: "sé lo que estás pensando", "no estás solo", "respira hondo"
- **Titulares de relleno**: "todo lo que necesitas saber sobre X", "guía definitiva de X", "X como un profesional"
- **CTA y transiciones de relleno**: "¿y tú qué opinas?", "vamos a desglosarlo", "la buena noticia es que…"

## Ejemplos de transformación

Consulta [references/ejemplos.md](references/ejemplos.md) para ver transformaciones completas de antes/después.

## Tabla de puntuación

Puntúa de 1 a 10 en cada dimensión (es para tu control interno; no incluyas la puntuación en la respuesta salvo que el usuario la pida):

| Dimensión | Pregunta clave |
|-----------|----------------|
| Naturalidad | ¿Suena a persona o a máquina? |
| Ritmo | ¿Varía la longitud de las frases o es monótono? |
| Confianza en el lector | ¿Respeta la inteligencia del lector o le explica todo? |
| Concreción | ¿Usa datos y ejemplos específicos o se queda en lo abstracto? |
| Densidad | ¿Sobra algo? ¿Cada palabra aporta? |

Por debajo de 35 sobre 50, o si fallan dos o más dimensiones: necesita revisión seria.

## Adaptación por tipo de texto

### Artículos, guías y tutoriales
- Tono de tú a tú, como entre amigos que saben del tema
- Expresiones muy españolas: "mola", "viene de perlas", "lo típico", "ojo con esto"
- Sin formalidades innecesarias, pero sin pasarse de colegueo

### Copy comercial y marketing
- Directo, sin rodeos
- Beneficios concretos, no adjetivos vacíos
- Evitar superlativos inflados: "el mejor", "revolucionario", "sin precedentes"

### Documentación técnica
- Clara y precisa, sin adornos
- Instrucciones directas: "haz esto", "ve aquí", "configura así"
- Sin introducciones filosóficas sobre el tema

### Emails y comunicación profesional
- Natural pero respetuoso
- Tutea o trata de usted según el destinatario y el contexto; no fuerces el tuteo
- Sin muletillas de cortesía excesiva
- Ir al grano desde la primera línea

### Redes sociales
- Conversacional y breve
- Sin emojis salvo que el usuario los pida
- Sin hashtags artificiales ni frases motivacionales

## Proceso de revisión

Cuando revises texto generado:

1. Lee el texto completo de un tirón. Si algo "suena a IA", márcalo.
2. Repasa las palabras contra [references/palabras.md](references/palabras.md). Donde una aparezca por inercia o se repita, sustitúyela; donde sea la más precisa y aparezca una sola vez, déjala. No hagas reemplazo automático: decide caso por caso.
3. Repasa las estructuras contra [references/estructuras.md](references/estructuras.md) y los ganchos contra [references/ganchos.md](references/ganchos.md). Reescribe los patrones que se repiten o suenan forzados, no todos por defecto.
4. Varía el ritmo: rompe series de frases con longitud similar, pero sin pasarte a frases-río.
5. Elimina todo lo que no aporte información nueva.
6. Revisa las costuras: que no quede coma tras un complemento corto, ni punto y coma de más, ni un titular en mayúsculas de estilo inglés. Si una frase suena a discurso institucional, reescríbela.
7. Puntúa con la tabla de arriba (uso interno). Si está por debajo de 35 o fallan dos dimensiones, revisa de nuevo.

## Notas importantes

- Esta skill no cambia el significado ni los datos del texto, solo la forma de expresarlos.
- No se trata de escribir mal a propósito ni de meter errores. Se trata de escribir con personalidad.
- Un texto puede ser preciso, bien documentado y profesional sin sonar a robot.
- Las listas de palabras no son absolutas, con una única excepción: la lista de «prohibición absoluta». El resto puede usarse si la palabra es la más precisa para el contexto; lo que se evita es el uso automático y recurrente.
- **No sobrecorrijas.** Cada regla tiene su extremo contrario, y pasarse es otro patrón de IA: no conviertas las frases cortas en frases-río, no elimines toda la voz pasiva (hay impersonales con «se» naturales) ni todos los conectores, no pases de un titular en mayúsculas de estilo inglés a otro todo en minúsculas, no erradiques el punto y coma. Modera y alterna, no saltes al otro lado.
- **Ajusta el registro al tipo de texto.** Lo coloquial («mola», «viene de perlas», el tuteo) es para artículos, tutoriales y redes. En informes, documentación y correo formal, sé natural pero sobrio, y usa usted si el contexto lo pide. Naturalidad no es lo mismo que coloquialismo.

## Referencias

- [Wikipedia: Signs of AI writing](https://en.wikipedia.org/wiki/Wikipedia:Signs_of_AI_writing) — Guía de WikiProject AI Cleanup
- [stop-slop](https://github.com/hardikpandya/stop-slop) — Skill de Hardik Pandya para eliminar patrones IA (MIT)
- [humanizer](https://github.com/blader/humanizer) — Skill de blader basada en Wikipedia (MIT)
- [The AI-isms of Writing Bible](https://docs.google.com/document/d/1l3OLrnWaXUqH0ycS-0so65Hd6ayxSQqUypRtrFHMt3M/) — Documento comunitario de patrones IA
- [Novelcrafter: AI-isms](https://www.novelcrafter.com/help/faq/ai-and-prompting/ai-isms) — Lista de la comunidad de escritores de ficción
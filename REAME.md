# Yet Another Form Library, but it works with all frameworks (or without)

**This package is a form library that doesn't need forms tightly coupled with complex view libraries** (i.e. Twig, Blade, Smarty, ecc.).  

During my developer ages (30 years ... still working ... crazy) I was in trouble when Smarty (a still active template engine) didn't update its library (for almost 5 years!!!) or when Zend Framework (ZF1) left their original view system (now it's Laminas).  
If you have a very big code base to mantain and if you are using a "**make it all**" framework, something could go wrong. A small "Black Swan" can force you to replace tens of thousands of code lines.  

There was something very interesting in ZF1 and it was the decorator pattern for rendering forms (when I was young in 2008).  
It wasn't really well done, but inspired me.   
Ok Ok ... when you talk about forms what should I use for rendering? I need something modular or future versions of Bootstrap will eat my developer time.  

Which is a good, modular template library without meta languages to learn?  
[Twig](https://twig.symfony.com/) is a very nice template engine but you don't use php. Symfony drop support for a their php template engine.  
What about [Plates](https://platesphp.com/)? Well, onestly I don't like using custom "tags/function" inside a page.html file to render something.  

Let's discover "Nested Closures". I know ... nobody has the truth, but everybody is allowed to think different.    
And here it comes [Smart Template](https://github.com/dalpras/smart-template) (... I couldnt find anything so I have mine).  

Great ... I have a full working modular form library that works low level with a few lines of code.
This form library has "zero" dependencies (or at least close to zero) with a new simple rendering decorator pattern.   

## How it works?

Beatiful words, but how can I use it?

```php

    // get FormZero Workinh ProjectDirectory
    $reflector = new ReflectionClass(FormFactory::class);
    $filename = $reflector->getFileName();
    $directory = dirname($filename);

    // instantiate TemplateEngine
    $template = new TemplateEngine($directory . '/../Template', 'form.php');
    $template->addCustomParamCallback('{attributes}', function($param) {
        return ($param === null) ? '' : $this->template->attributes($param);
    });

    $serverRequest = new (\Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory(
            new ServerRequestFactory(), 
            new StreamFactory(), 
            new UploadedFileFactory(), 
            new ResponseFactory()
        ))->createRequest($request);

    $session = (new \Symfony\Component\HttpFoundation\Session\Session())->start();

    // with CSRF token
    $formFactory = new FormFactory($template, 'form.php', $serverRequest, $session);
    // without CSRF token
    $formFactory = new FormFactory($template, 'form.php', $serverRequest);

    // form.php is the nested array of Form templates that has to be in the TemplateEngine "FormTemplateDir"
    $form = $formFactory->createForm(ZeroForm::class);
    $form->add('text', 'name');

    echo $form->render();
```



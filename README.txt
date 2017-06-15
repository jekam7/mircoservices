I have divided the project into several mircoservices. Each of them interacts with others via message bus (RabbitMQ). 
I have also created API gateway that interacts with the service via REST-API.  

There were 2 projects created based on Symfony
1. API gateway
2. Product microservice

To create a product in a store, the user makes REST-request on API gateway. API gateway makes authorization and sends a request to Product microservice. 
Product microservice creates a product and produces a message in message bus where consumers can get it. 
I have also created a test that you can see it in product/src/AppBundle/Tests/Controller/
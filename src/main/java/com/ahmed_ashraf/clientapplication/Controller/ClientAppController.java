package com.ahmed_ashraf.clientapplication.Controller;

import com.ahmed_ashraf.clientapplication.Service.*;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

//@RestController
@RequestMapping("/api/clientapp")
public class ClientAppController {

    @Autowired
    private final ClientAppService service;

    @Autowired
    private DeleteClientAppService dservice;

    public ClientAppController(ClientAppService service, DeleteClientAppService dservice) {
        this.service = service;
        this.dservice = dservice;
    }
/*
    @GetMapping("/serials")
    public ResponseEntity<List<SerialNationalDTO>> getSerials() {
        System.out.println("✅ Received request for /api/clientapp/serials");
        return ResponseEntity.ok(service.getAllSerialsAndNationalnos());
    }*/

    @PostMapping("/delete")
    public ResponseEntity<String> deleteClientAppData() {
        System.out.println("🗑Received request for /api/clientapp/delete");
    //    dservice.deleteClientApps(service.getAllSerialsAndNationalnos());
       // return ResponseEntity.ok("Deleted successfully");
        String status = dservice.deleteClientApps(service.getAllSerialsAndNationalnos());
        if ("OK".equalsIgnoreCase(status)) {
            return ResponseEntity.ok("Records deleted successfully and archived.");
        } else {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body("Failed to delete some or all records.");
        }
    }
}


package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.Column;
import jakarta.persistence.Embeddable;
import lombok.*;

import java.io.Serializable;

@Embeddable
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class DMClientAppDeletedId implements Serializable {
    @Column(name = "SERIAL")
    private Long serial;

    @Column(name = "NATIONALNO")
    private String nationalno;
}
